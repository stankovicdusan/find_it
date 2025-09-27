<?php

namespace App\Controller\Dashboard\Backlog;

use App\Controller\BaseController;
use App\Entity\Project;
use App\Entity\Ticket;
use App\Form\Type\BacklogAddToSprintType;
use App\Repository\TicketRepository;
use App\Service\SprintService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/{key}/backlog', name: 'backlog_', methods: ['GET'])]
class BacklogController extends BaseController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function index(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        EntityManagerInterface $em,
    ): Response {
        return $this->render('dashboard/sprint_backlog/index.html.twig', [
            'project'          => $project,
            'tickets'          => $em->getRepository(Ticket::class)->findBacklogByProject($project),
            'completedTickets' => $em->getRepository(Ticket::class)->findCompletedBacklogByProject($project),
            'activeMenu'       => 'backlog',
        ]);
    }

    #[Route('/list-partial', name: 'list_partial', methods: ['GET'])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function listPartial(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        TicketRepository $ticketRepo
    ): Response {
        return $this->render('dashboard/sprint_backlog/backlog_list.html.twig', [
            'project' => $project,
            'tickets' => $ticketRepo->findBacklogByProject($project),
        ]);
    }

    #[Route('/form/add', name: 'form_add', methods: ['GET'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function formBacklogAdd(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project
    ): Response {
        $form = $this->createForm(BacklogAddToSprintType::class, null, [
            'project' => $project,
            'action'  => $this->generateUrl('backlog_add', ['key' => $project->getKey()]),
            'method'  => 'POST',
        ]);

        return $this->render('dashboard/sprint_backlog/form_backlog_add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['POST'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function backlogAdd(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        SprintService $service,
    ): JsonResponse {
        $form = $this->createForm(BacklogAddToSprintType::class, null, ['project' => $project])
            ->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse([
                'ok'   => false,
                'html' => $this->renderView('dashboard/sprint_backlog/form_backlog_add.html.twig', [
                    'form' => $form->createView(),
                ]),
            ], 422);
        }

        $data    = $form->getData();
        $sprint  = $data['sprint'];
        $tickets = $data['tickets']->toArray();

        $service->addTicketsToSprint($sprint, $tickets);

        return new JsonResponse(['ok' => true]);
    }
}