<?php

namespace App\Controller\Dashboard;

use App\Controller\BaseController;
use App\Entity;
use App\Form\Type\TicketCommentType;
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
    #[Route(path: '', name: 'index', methods: ["GET"])]
    public function index(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        EntityManagerInterface $em,
    ): Response {
        $isScrum = $project->isScrumProject();

        $activeSprint = null;
        if ($isScrum) {
            $activeSprint = $em->getRepository(Entity\Sprint::class)->findActiveForProject($project);
        }

        if ($isScrum && $activeSprint) {
            $workflowStatuses = $em->getRepository(Entity\WorkflowStatus::class)->findByProjectWithTicketsForSprint($project, $activeSprint);

            $ticketsByStatus = [];
            foreach ($workflowStatuses as $s) {
                $ticketsByStatus[$s->getId()] = $s->getTickets()->toArray();
            }
        } else {
            $workflowStatuses = $em->getRepository(Entity\WorkflowStatus::class)->findByProjectWithTickets($project);

            $ticketsByStatus = [];
            foreach ($workflowStatuses as $s) {
                $ticketsByStatus[$s->getId()] = $isScrum ? [] : $s->getTickets()->toArray();
            }
        }

        return $this->render('dashboard/board/index.html.twig', [
            'project'          => $project,
            'workflowStatuses' => $workflowStatuses,
            'ticketsByStatus'  => $ticketsByStatus,
            'issueTypes'       => $em->getRepository(Entity\IssueType::class)->findAll(),
            'activeMenu'       => 'board',
            'activeSprint'     => $activeSprint,
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
        $form = $this->createForm(TicketCommentType::class);

        return $this->render('dashboard/tickets/ticket_modal.html.twig', [
            'project'     => $project,
            'ticket'      => $ticket,
            'commentForm' => $form->createView(),
        ]);
    }

    #[Route('/ticket/new', name: 'ticket_create_form', methods: ['GET'])]
    public function viewForm(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
    ): Response {
        $form = $this->createForm(TicketType::class);

        return $this->render('dashboard/tickets/create_ticket_modal.html.twig', [
            'project' => $project,
            'form'    => $form->createView(),
        ]);
    }

    #[Route('/ticket/new', name: 'ticket_create_submit', methods: ['POST'])]
    public function createSubmit(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        EntityManagerInterface $em,
        TicketService $ticketService,
    ): JsonResponse {
        $ticket = new Entity\Ticket();

        $form = $this->createForm(TicketType::class, $ticket, [
            'action' => $this->generateUrl('dashboard_ticket_create_submit', ['key' => $project->getKey()]),
            'method' => 'POST',
        ])->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse([
                'ok'   => false,
                'html' => $this->renderView('dashboard/tickets/create_ticket_modal.html.twig', [
                    'project' => $project,
                    'form'    => $form->createView(),
                ]),
            ], 422);
        }

        $ticketService->createTicket($ticket, $project, $this->getUser());

        $status = $ticket->getStatus();

        $tickets = $em->getRepository(Entity\Ticket::class)->findBy(['status' => $status], ['order' => 'ASC']);

        $ticketsHtml = $this->renderView('dashboard/tickets/ticket_items.html.twig', [
            'project' => $project,
            'tickets' => $tickets,
        ]);

        return new JsonResponse([
            'ok'         => true,
            'statusId'   => $status->getId(),
            'ticketsHtml'=> $ticketsHtml,
            'badgeCount' => \count($tickets),
        ]);
    }
}