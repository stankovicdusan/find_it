<?php

namespace App\Controller\Dashboard\Workflow;

use App\Controller\BaseController;
use App\Entity;
use App\Service\WorkflowStatusService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('PROJECT_ADMIN', subject: 'project')]
#[Route('/dashboard/{key}/workflows', name: 'workflow_status_')]
class WorkflowStatusController extends BaseController
{
    #[Route(
        path: '',
        name: 'index',
        methods: ["GET"]
    )]
    public function index(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
    ): Response {
        return $this->render('dashboard/workflows/index.html.twig', [
            'project'    => $project,
            'activeMenu' => 'workflows',
        ]);
    }

    #[Route(
        path: '/status/form/{status?}',
        name: 'form',
        defaults: ['status' => null],
        methods: ['GET']
    )]
    public function statusForm(
        WorkflowStatusService $workflowStatusService,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        ?Entity\WorkflowStatus $status = null,
    ): Response {
        return $workflowStatusService->renderForm($project, $status);
    }

    #[Route(
        path: '/status/form/{status}',
        name: 'update',
        defaults: ['status' => null],
        methods: ['POST']
    )]
    public function updateWorkflow(
        Request $request,
        WorkflowStatusService $workflowStatusService,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        ?Entity\WorkflowStatus $status = null,
    ): Response {
        return $workflowStatusService->handleSave($request, $project, $status);
    }

    #[Route(
        path: '/status/{status}/delete',
        name: 'delete',
        methods: ['POST']
    )]
    public function deleteStatus(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\WorkflowStatus $status,
        WorkflowStatusService $workflowStatusService
    ): Response {
        if ($status->getWorkflow()->getProject() !== $project) {
            return new JsonResponse(['message' => 'Status does not belong to this project.'], 403);
        }

        $statusId = $status->getId();
        try {
            $workflowStatusService->deleteIfEmpty($status);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        }

        $statuses = $project->getWorkflow()->getStatuses();

        return new JsonResponse([
            'ok'   => true,
            'html' => $this->renderView('dashboard/workflows/statuses_list.html.twig', [
                'project'  => $project,
                'statuses' => $statuses,
            ]),
            'currentStatus' => $statusId,
        ]);
    }
}
