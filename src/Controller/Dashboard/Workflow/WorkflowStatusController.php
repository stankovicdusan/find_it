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

    #[Route('/status/{status}/delete-check', name: 'delete_check', methods: ['GET'])]
    public function deleteCheck(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\WorkflowStatus $status,
        WorkflowStatusService $service
    ): JsonResponse {
        $res = $service->prepareDelete($project, $status);
        $code = $res['code'] ?? 200;

        return new JsonResponse($res, $code);
    }

    #[Route('/status/{status}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $req,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\WorkflowStatus $status,
        WorkflowStatusService $service,
    ): JsonResponse {
        $targetId = $req->request->getInt('targetStatusId') ?: null;
        $token    = (string) $req->request->get('_token', '');

        $res  = $service->delete($project, $status, $targetId, $token);
        $code = $res['code'] ?? ($res['ok'] ? 200 : 422);

        return new JsonResponse($res, $code);
    }

    #[Route(
        path: '/reorder',
        name: 'reorder',
        methods: ['POST']
    )]
    public function sortStatuses(
        Request $req,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        WorkflowStatusService $workflowStatusService
    ): JsonResponse {
        $ids  = $req->request->all('ids');
        $csrf = (string) $req->request->get('_token', '');

        $res  = $workflowStatusService->sortStatuses($project, $ids, $csrf);
        $code = $res['code'] ?? ($res['ok'] ? 200 : 422);

        return new JsonResponse($res, $code);
    }
}
