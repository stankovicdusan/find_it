<?php

namespace App\Controller\Project;

use App\Controller\BaseController;
use App\Entity;
use App\Model\Dto\CreateProjectDto;
use App\Service\ProjectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[Route(path: '/projects')]
class ProjectController extends BaseController
{
    #[Route(path: '', name: 'projects_index', methods: ["GET"])]
    public function index(EntityManagerInterface $em): Response
    {
        $projects = $em->getRepository(Entity\Project::class)->findForUser($this->getLoggedInUser());

        return $this->render('projects/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route(path: '/new', name: 'project_new', methods: ["GET"])]
    public function new(EntityManagerInterface $em): Response
    {
        $templates = $em->getRepository(Entity\ProjectTemplate::class)->findAll();

        return $this->render('projects/create.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route(path: '/create', name: 'project_create', methods: ["POST"])]
    public function create(
        #[MapRequestPayload] CreateProjectDto $dto,
        ProjectService $projectService,
    ): Response {
        $project = $projectService->create($dto, $this->getLoggedInUser());

        return $this->redirectToRoute('dashboard_index', [
            'key' => $project->getKey(),
        ]);
    }

    #[Route(path: '/validate-key', name: 'project_key_validation', methods: ["POST"])]
    public function validateKey(
        Request $request,
        ProjectService $projectService,
    ): JsonResponse {
        return new JsonResponse([
            'data' => $projectService->doesKeyAlreadyExistsByName($request->get('key')),
        ]);
    }
}
