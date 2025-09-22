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
use Twig\Environment;

class WorkflowStatusService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FormFactoryInterface $forms,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
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
        if (!$workflow->getStatuses()->isEmpty() && count($toStatusIds) < 1) {
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

        if (null === $status->getId()) {
            $this->em->persist($status);
        }

        $this->em->flush();
        $this->em->refresh($workflow);
        $this->em->refresh($status);
    }

    public function deleteIfEmpty(WorkflowStatus $status): void
    {
        $count = $status->getWorkflow()->getStatuses()->count();

        if ($count <= 2) {
            throw new DomainException('Workflow must have at least 2 statuses.');
        }

        if ($status->getTickets()->count() > 0) {
            throw new \RuntimeException('Cannot delete: this status has tickets.');
        }

        $this->em->remove($status);
        $this->em->flush();
    }

    private function getWorkflowStatus(Workflow $workflow, ?WorkflowStatus $status = null): WorkflowStatus
    {
        if (null !== $status) {
            return $status;
        }

        $status = new WorkflowStatus();

        $status->setWorkflow($workflow);
        $status->setSortOrder(1); // temporary

        return $status;
    }
}