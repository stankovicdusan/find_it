<?php

namespace App\Controller\Dashboard;

use App\Controller\BaseController;
use App\Entity;
use App\Form\Type\TicketType;
use App\Service\BoardSearchService;
use App\Service\TicketService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/dashboard/{key}', name: 'dashboard_')]
class DashboardController extends BaseController
{
    #[Route(path: '', name: 'index', methods: ["GET", "POST"])]
    public function index(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        TicketService $ticketService,
        EntityManagerInterface $em,
    ): Response {
        $ticket = new Entity\Ticket();
        $form = $this->createForm(TicketType::class, $ticket, [
            'project' => $project,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $ticketService->createTicket($ticket, $project, $this->getUser());

            return $this->redirectToRoute('dashboard_index', [
                'key' => $project->getKey(),
            ]);
        }

        $allStatuses = $project->getWorkflow()->getStatuses()->toArray();
        $ticketsByStatus = [];
        foreach ($allStatuses as $s) {
            $ticketsByStatus[$s->getId()] = $s->getTickets()->toArray();
        }

        return $this->render('dashboard/board/index.html.twig', [
            'project'          => $project,
            'workflowStatuses' => $em->getRepository(Entity\WorkflowStatus::class)->findByProjectWithTickets($project),
            'ticketsByStatus'  => $ticketsByStatus,
            'form'             => $form,
            'issueTypes'       => $em->getRepository(Entity\IssueType::class)->findAll(),
            'activeMenu'       => 'board',
        ]);
    }

    #[Route('/board/search', name: 'board_search', methods: ['GET'])]
    public function searchBoard(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        BoardSearchService $boardSearch,
    ): Response {
        $html = $boardSearch->renderColumns($project, $request->query->get('q', ''));

        return new Response($html);
    }


    #[Route(path: '/move', name: 'ticket_move', methods: ["POST"])]
    public function ticketMove(
        Request $req,
        TicketService $ticketService,
        EntityManagerInterface $em,
    ): JsonResponse {
        $ticketId  = (int) $req->request->get('ticketId');
        $toStatusId= (int) $req->request->get('toStatusId');
        $order     = $req->request->all('order');

        $ticket = $em->getRepository(Entity\Ticket::class)->find($ticketId);
        $to     = $em->getRepository(Entity\WorkflowStatus::class)->find($toStatusId);

        if (!$ticketService->canMoveTo($ticket->getStatus(), $to)) {
            return new JsonResponse(['ok' => false, 'message' => 'Transition not allowed.'], 422);
        }

        $ticketService->moveTicket($ticket, $to, $order);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/ticket/{id}/modal', name: 'ticket_modal', methods: ['GET'])]
    public function ticketModal(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\Ticket $ticket,
    ): Response {
        return new Response(
            $this->renderView('dashboard/board/ticket_modal.html.twig', [
                'project' => $project,
                'ticket'  => $ticket,
            ])
        );
    }
}