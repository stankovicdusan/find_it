<?php

namespace App\Controller\Dashboard\Workflow;

use App\Controller\BaseController;
use App\Entity;
use App\Service\WorkflowService;
use App\Service\WorkflowStatusService;
use App\Form\Type\WorkflowStatusType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class WorkflowController extends BaseController
{
    #[Route(path: '/dashboard/{key}/workflows', name: 'dashboard_workflow_index', methods: ["GET"])]
    public function index(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
    ): Response {
        return $this->render('dashboard/workflows/index.html.twig', [
            'project'    => $project,
            'activeMenu' => 'workflows',
        ]);
    }

    #[Route(path: '/dashboard/{key}/workflows/form/{status}', name: 'workflow_status_form', defaults: ['status' => null], methods: ['GET'])]
    public function statusForm(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        ?Entity\WorkflowStatus $status = null,
    ): Response {
        $title = null === $status ? "Add Status" : "Edit Status: {$status->getTitle()}";

        $routeParameters = ['key' => $project->getKey()];
        if (null !== $status) {
            $routeParameters['status'] = $status->getId();
        }

        $form = $this->createForm(WorkflowStatusType::class, $status, [
            'available_statuses' => array_filter($project->getWorkflow()->getStatuses()->toArray(), fn($s) => $s !== $status),
            'action'             => $this->generateUrl('workflow_status_update', $routeParameters),
        ]);

        return $this->render('dashboard/workflows/modal_form.html.twig', [
            'form'  => $form->createView(),
            'title' => $title,
        ]);
    }

    #[Route(path: '/dashboard/{key}/workflows/form/{status}', name: 'workflow_status_update', defaults: ['status' => null], methods: ['POST'])]
    public function updateWorkflow(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        ?Entity\WorkflowStatus $status,
        WorkflowStatusService $service
    ): Response {
        $workflow = $project->getWorkflow();
        if (!$workflow) {
            return new JsonResponse(['error' => 'Workflow not found'], 404);
        }

        $isAdd = null === $status;
        if ($isAdd) {
            $status = $service->newFor($project);
        }

        $available = array_filter($workflow->getStatuses()->toArray(), fn($s) => $s !== $status);
        $form = $this->createForm(WorkflowStatusType::class, $status, [
            'available_statuses' => $available,
            'action' => $this->generateUrl('workflow_status_update', [
                'key'    => $project->getKey(),
                'status' => $status->getId() ?: 'new',
            ]),
        ]);

        $form->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $title = $isAdd ? 'Add Status' : "Edit Status: {$status->getTitle()}";
            $html = $this->renderView('dashboard/workflows/modal_form.html.twig', [
                'form'  => $form->createView(),
                'title' => $title,
            ]);

            return new Response($html, 422);
        }

        $toStatuses = $form->has('transitions') ? ($form->get('transitions')->getData() ?? []) : [];
        $toIds = array_map(fn($s) => $s->getId(), $toStatuses);

        $service->save($status, $toIds);

        $rowHtml = $this->renderView('dashboard/workflows/status_row.html.twig', [
            'project' => $project,
            'status'  => $status,
        ]);

        return $this->json([
            'success'  => true,
            'id'       => $status->getId(),
            'row_html' => $rowHtml,
            'is_new'   => $isAdd,
        ]);
    }

    #[Route(
        path: '/dashboard/{key}/workflows/status/{status}/delete',
        name: 'workflow_status_delete',
        methods: ['POST']
    )]
    public function deleteStatus(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\WorkflowStatus $status,
        CsrfTokenManagerInterface $csrf,
        WorkflowStatusService $workflowStatusService
    ): Response {
        // CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_status_' . $status->getId(), $token)) {
            return new JsonResponse(['message' => 'Invalid CSRF token.'], 400);
        }

        // (Optional) Ensure status belongs to this project
        if ($status->getWorkflow()->getProject() !== $project) {
            return new JsonResponse(['message' => 'Status does not belong to this project.'], 403);
        }

        try {
            $workflowStatusService->deleteIfEmpty($status);
        } catch (\RuntimeException $e) {
            // Has tickets or blocked
            return new JsonResponse(['message' => $e->getMessage()], 409);
        }

        return new JsonResponse(['success' => true, 'id' => $status->getId()]);
    }
}
