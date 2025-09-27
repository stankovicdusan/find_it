<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\Workflow;
use App\Entity\WorkflowStatus;
use App\Entity\WorkflowTransition;
use App\Form\Type\WorkflowStatusType;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

class WorkflowStatusService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FormFactoryInterface $forms,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrf,
    ) {
    }

    public function renderForm(
        Project $project,
        ?WorkflowStatus $status
    ): Response {
        $form = $this->forms->create(WorkflowStatusType::class,
            $status,
            [
                'available_statuses'      => array_filter($project->getWorkflow()->getStatuses()->toArray(), fn($s) => $s !== $status),
                'preselected_transitions' => $status
                    ? array_map(fn($t) => $t->getToStatus(), $status->getTransitions()->toArray())
                    : [],
                'action' => $this->urlGenerator->generate(
                    'workflow_status_update',
                    [
                        'key'    => $project->getKey(),
                        'status' => $status?->getId(),
                    ],
                ),
            ],
        );

        return new Response($this->twig->render('dashboard/workflows/modal_form.html.twig', [
            'form'   => $form->createView(),
            'status' => $status,
        ]));
    }

    public function handleSave(
        Request $request,
        Project $project,
        ?WorkflowStatus $status = null,
    ): JsonResponse {
        $status = $this->getWorkflowStatus($project->getWorkflow(), $status);

        $form = $this->forms->create(WorkflowStatusType::class,
            $status,
            [
                'available_statuses'      => array_filter($project->getWorkflow()->getStatuses()->toArray(), fn($s) => $s !== $status),
                'preselected_transitions' => $status
                    ? array_map(fn($t) => $t->getToStatus(), $status->getTransitions()->toArray())
                    : [],
                'action' => $this->urlGenerator->generate(
                    'workflow_status_update',
                    [
                        'key'    => $project->getKey(),
                        'status' => $status->getId(),
                    ],
                ),
            ],
        )->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse([
                'ok'   => false,
                'html' => $this->twig->render('dashboard/workflows/modal_form.html.twig', [
                    'form'   => $form->createView(),
                    'status' => $status,
                ]),
            ], 422);
        }

        $toStatuses = $form->has('transitions') ? ($form->get('transitions')->getData() ?? []) : [];
        $toStatusIds = array_map(fn($s) => $s->getId(), $toStatuses);

        $this->save($toStatusIds, $project->getWorkflow(), $status);

        $statuses = $project->getWorkflow()->getStatuses();

        return new JsonResponse([
            'ok'   => true,
            'html' => $this->twig->render('dashboard/workflows/statuses_list.html.twig', [
                'project'  => $project,
                'statuses' => $statuses,
            ]),
            'currentStatus' => $status->getId(),
        ]);
    }

    /**
     * @param int[] $toStatusIds
     */
    private function save(
        array $toStatusIds,
        Workflow $workflow,
        WorkflowStatus $status,
    ): void {
        if (!$workflow->getStatuses()->isEmpty() && count($toStatusIds) < 1 && !$status->isFinal()) {
            throw new \DomainException('Select at least one transition.');
        }

        if ($status->isInitial()) {
            foreach ($workflow->getStatuses() as $otherStatus) {
                if ($otherStatus !== $status && $otherStatus->isInitial()) {
                    $otherStatus->setIsInitial(false);
                }
            }
        }

        $status->getTransitions()->clear();

        if (!$status->isFinal()) {
            $statusById = [];
            foreach ($workflow->getStatuses() as $wfStatus) {
                $statusById[$wfStatus->getId()] = $wfStatus;
            }

            foreach ($toStatusIds as $rawId) {
                if (!isset($statusById[$rawId])) {
                    continue;
                }

                $toStatus = $statusById[$rawId];

                if ($toStatus === $status) {
                    continue;
                }

                $transition = new WorkflowTransition();

                $transition->setFromStatus($status);
                $transition->setToStatus($toStatus);

                $this->em->persist($transition);
            }
        }

        if (null === $status->getId()) {
            $this->em->persist($status);
        }

        $this->em->flush();
        $this->em->refresh($workflow);
        $this->em->refresh($status);
    }

    public function prepareDelete(Project $project, WorkflowStatus $status): array
    {
        if ($status->getWorkflow()->getProject()->getId() !== $project->getId()) {
            return ['ok' => false, 'message' => 'Not found', 'code' => 404];
        }

        if ($status->isInitial() || $status->isFinal()) {
            $msg = $status->isInitial()
                ? 'Initial status cannot be deleted.'
                : 'Final status cannot be deleted.';

            return ['ok' => false, 'message' => $msg, 'code' => 422];
        }

        $ticketsCount = $status->getTickets()->count();
        if ($ticketsCount === 0) {
            return ['ok' => true, 'canDelete' => true];
        }

        $other = [];
        foreach ($status->getWorkflow()->getStatuses() as $s) {
            if ($s->getId() !== $status->getId()) $other[] = $s;
        }

        $html = $this->twig->render('dashboard/workflows/status_delete_reassign.html.twig', [
            'status'        => $status,
            'ticketsCount'  => $ticketsCount,
            'otherStatuses' => $other,
            'postUrl'       => $this->urlGenerator->generate('workflow_status_delete', [
                'key'    => $project->getKey(),
                'status' => $status->getId(),
            ]),
        ]);

        return ['ok' => true, 'needsReassign' => true, 'html' => $html];
    }

    public function delete(Project $project, WorkflowStatus $status, ?int $targetStatusId, ?string $csrfToken): array
    {
        if (!$this->csrf->isTokenValid(new CsrfToken('workflow_status_delete_' . $status->getId(), (string) $csrfToken))) {
            return ['ok' => false, 'message' => 'Invalid CSRF', 'code' => 403];
        }

        if ($status->isInitial() || $status->isFinal()) {
            $msg = $status->isInitial()
                ? 'Initial status cannot be deleted.'
                : 'Final status cannot be deleted.';

            return ['ok' => false, 'message' => $msg, 'code' => 422];
        }

        $all = $status->getWorkflow()->getStatuses();
        if ($all->count() <= 2) {
            return ['ok' => false, 'message' => 'At least 2 statuses required.', 'code' => 422];
        }

        if ($status->getWorkflow()->getProject()->getId() !== $project->getId()) {
            return ['ok' => false, 'message' => 'Not found', 'code' => 404];
        }

        $tickets = $status->getTickets();
        if ($tickets->count() > 0) {
            if (!$targetStatusId) {
                return ['ok' => false, 'message' => 'Target status required.', 'code' => 422];
            }

            $target = null;
            foreach ($all as $s) {
                if ($s->getId() === $targetStatusId) { $target = $s; break; }
            }

            if (!$target || $target->getId() === $status->getId()) {
                return ['ok' => false, 'message' => 'Invalid target status.', 'code' => 422];
            }

            foreach ($tickets as $t) {
                $t->setStatus($target);
            }
        }

        $this->em->remove($status);
        $this->em->flush();

        $html = $this->twig->render('dashboard/workflows/statuses_list.html.twig', [
            'project'  => $project,
            'statuses' => $project->getWorkflow()->getStatuses(),
        ]);

        return ['ok' => true, 'html' => $html];
    }

    public function sortStatuses(Project $project, array $ids, string $csrfToken): array
    {
        if (!$this->csrf->isTokenValid(new CsrfToken('workflow_status_reorder_' . $project->getId(), $csrfToken))) {
            return ['ok' => false, 'message' => 'Invalid CSRF', 'code' => 403];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return ['ok' => false, 'message' => 'No items', 'code' => 422];
        }

        $statuses = $this->em->getRepository(WorkflowStatus::class)->getSortedStatuses($project);
        $byId = [];
        foreach ($statuses as $s) {
            $byId[$s->getId()] = $s;
        }

        $order = 0;
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $byId[$id]->setSortOrder($order++);
            }
        }

        foreach ($statuses as $s) {
            if (!in_array($s->getId(), $ids, true)) {
                $s->setSortOrder($order++);
            }
        }

        $this->em->flush();

        return ['ok' => true];
    }

    private function getWorkflowStatus(Workflow $workflow, ?WorkflowStatus $status = null): WorkflowStatus
    {
        if (null !== $status) {
            return $status;
        }

        $status = new WorkflowStatus();

        $status->setWorkflow($workflow);
        $status->setSortOrder($this->em->getRepository(WorkflowStatus::class)->getNextOrderBasedOnWorkflow($workflow));

        return $status;
    }
}