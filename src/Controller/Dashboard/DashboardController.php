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
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
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
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function searchBoard(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        BoardSearchService $boardSearch,
    ): Response {
        $html = $boardSearch->renderColumns($project, $request->query->get('q', ''));

        return new Response($html);
    }

    #[Route(path: '/move', name: 'ticket_move', methods: ["POST"])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function ticketMove(
        Request $req,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        TicketService $ticketService,
        EntityManagerInterface $em,
    ): JsonResponse {
        $ticketId   = (int) $req->request->get('ticketId');
        $toStatusId = (int) $req->request->get('toStatusId');
        $order      = $req->request->all('order');

        $ticket = $em->getRepository(Entity\Ticket::class)->find($ticketId);
        $to     = $em->getRepository(Entity\WorkflowStatus::class)->find($toStatusId);

        if (!$ticketService->canMoveTo($ticket->getStatus(), $to)) {
            return new JsonResponse(['ok' => false, 'message' => 'Transition not allowed.'], 422);
        }

        $ticketService->moveTicket($ticket, $to, $order);

        $tickets = $em->getRepository(Entity\Ticket::class)->findCompletedBacklogByProject($project);

        return new JsonResponse([
            'ok' => true,
            'htmlCompleted' => $this->renderView('dashboard/sprint_backlog/backlog_list.html.twig', [
                'project' => $project,
                'tickets' => $tickets,
            ]),
            'htmlOthers' => $this->renderView('dashboard/sprint_backlog/backlog_list.html.twig', [
                'project' => $project,
                'tickets' => $em->getRepository(Entity\Ticket::class)->findBacklogByProject($project),
            ]),
            'countOfCompletedTickets' => count($tickets),
        ]);
    }

    #[Route(path: '/change-assignee', name: 'ticket_change_assignee', methods: ["POST"])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function changeTicketAssignee(
        Request $req,
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        TicketService $ticketService,
        EntityManagerInterface $em,
    ): JsonResponse {
        $ticketId = (int) $req->request->get('ticketId');
        $userId   = (int) $req->request->get('userId');

        $ticket = $em->getRepository(Entity\Ticket::class)->find($ticketId);
        $user   = $em->getRepository(Entity\User::class)->find($userId);

        $ticketService->changeAssignee($ticket, $user);

        return new JsonResponse([
            'ok' => true,
            'html' => $this->renderView('dashboard/sprint_backlog/backlog_list.html.twig', [
                'project' => $project,
                'tickets' => $em->getRepository(Entity\Ticket::class)->findBacklogByProject($project),
            ]),
        ]);
    }

    #[Route('/ticket/{id}/modal', name: 'ticket_modal', methods: ['GET'])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function ticketModal(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
        Entity\Ticket $ticket,
        EntityManagerInterface $em,
    ): Response {
        $form = $this->createForm(TicketCommentType::class);

        $from = $ticket->getStatus();
        $allowedTo = [];
        foreach ($from->getTransitions() as $tr) {
            $allowedTo[] = $tr->getToStatus();
        }

        return $this->render('dashboard/tickets/ticket_modal.html.twig', [
            'project'      => $project,
            'ticket'       => $ticket,
            'commentForm'  => $form->createView(),
            'allowedTo'    => $allowedTo,
            'projectUsers' => $em->getRepository(Entity\ProjectUser::class)->getProjectMembers($project),
        ]);
    }

    #[Route('/ticket/new', name: 'ticket_create_form', methods: ['GET'])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function viewForm(
        #[MapEntity(mapping: ['key' => 'key'])] Entity\Project $project,
    ): Response {
        $form = $this->createForm(TicketType::class, null, [
            'project' => $project,
        ]);

        return $this->render('dashboard/tickets/create_ticket_modal.html.twig', [
            'project' => $project,
            'form'    => $form->createView(),
        ]);
    }

    #[Route('/ticket/new', name: 'ticket_create_submit', methods: ['POST'])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
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
            'project' => $project,
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

        $ticketService->createTicket($ticket, $project, $this->getLoggedInUser());

        $ticketsHtml = $this->renderView('dashboard/sprint_backlog/backlog_list.html.twig', [
            'project' => $project,
            'tickets' => $em->getRepository(Entity\Ticket::class)->findBacklogByProject($project),
        ]);

        return new JsonResponse([
            'ok'          => true,
            'ticketsHtml' => $ticketsHtml,
        ]);
    }
}