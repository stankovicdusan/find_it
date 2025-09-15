<?php

namespace App\Controller\Dashboard;

use App\Controller\BaseController;
use App\Entity;
use App\Form\Type\TicketType;
use App\Service\TicketService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardController extends BaseController
{
    #[Route(path: '/dashboard/{key}', name: 'dashboard_index', methods: ["GET", "POST"])]
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

        return $this->render('dashboard/board/index.html.twig', [
            'project'          => $project,
            'workflowStatuses' => $em->getRepository(Entity\WorkflowStatus::class)->findByProjectWithTickets($project),
            'form'             => $form,
            'issueTypes'       => $em->getRepository(Entity\IssueType::class)->findAll(),
            'activeMenu'       => 'board',
        ]);
    }
}