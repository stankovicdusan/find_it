<?php

namespace App\Controller\Dashboard\Sprint;

use App\Controller\BaseController;
use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Ticket;
use App\Form\Type\SprintCompleteType;
use App\Form\Type\SprintCreateType;
use App\Form\Type\SprintStartType;
use App\Repository\SprintRepository;
use App\Repository\TicketRepository;
use App\Service\SprintService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

#[Route('/dashboard/{key}/sprints', name: 'sprints_')]
class SprintController extends BaseController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function index(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        SprintRepository $repo
    ): Response {
        return $this->render('dashboard/sprint/index.html.twig', [
            'project'    => $project,
            'active'     => $repo->findActiveForProject($project),
            'planned'    => $repo->findPlannedForProject($project),
            'completed'  => $repo->findCompletedForProject($project),
            'activeMenu' => 'sprints',
        ]);
    }

    #[Route('/list-partial', name: 'list_partial', methods: ['GET'])]
    #[IsGranted('PROJECT_MEMBER', subject: 'project')]
    public function listPartial(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        SprintRepository $repo
    ): Response {
        return $this->render('dashboard/sprint/list.html.twig', [
            'project'   => $project,
            'active'    => $repo->findActiveForProject($project),
            'planned'   => $repo->findPlannedForProject($project),
            'completed' => $repo->findCompletedForProject($project, 20),
        ]);
    }

    #[Route('/form/create', name: 'form_create', methods: ['GET'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function createSprintForm(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project
    ): Response {
        $form = $this->createForm(SprintCreateType::class, null, [
            'action' => $this->generateUrl('sprints_create', ['key' => $project->getKey()]),
            'method' => 'POST',
        ]);

        return $this->render('dashboard/sprint/form_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function create(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        SprintService $service,
    ): JsonResponse {
        $sprint = new Sprint();
        $form = $this->createForm(SprintCreateType::class, $sprint)->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse([
                'ok'   => false,
                'html' => $this->renderView('dashboard/sprint/form_create.html.twig', [
                    'form' => $form->createView(),
                ]),
            ], 422);
        }

        $service->createPlanned($project, $sprint);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/form/start/{id}', name: 'form_start', methods: ['GET'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function formStart(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        Sprint $sprint
    ): Response {
        if ($sprint->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(SprintStartType::class, null, [
            'sprint' => $sprint,
            'action' => $this->generateUrl('sprints_start', [
                'key' => $project->getKey(),
                'id'  => $sprint->getId(),
            ]),
            'method' => 'POST',
        ]);

        return $this->render('dashboard/sprint/form_start.html.twig', [
            'form'   => $form->createView(),
            'sprint' => $sprint,
        ]);
    }

    #[Route('/start/{id}', name: 'start', methods: ['POST'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function start(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        Sprint $sprint,
        SprintService $service
    ): JsonResponse {
        if ($sprint->getProject()->getId() !== $project->getId()) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid sprint.'], 404);
        }

        $form = $this->createForm(SprintStartType::class)->handleRequest($request);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse([
                'ok'   => false,
                'html' => $this->renderView('dashboard/sprint/form_start.html.twig', [
                    'form'   => $form->createView(),
                    'sprint' => $sprint,
                ]),
            ], 422);
        }

        try {
            $data = $form->getData();
            if (!empty($data['shiftWindow'])) {
                $service->shiftPlannedWindowToNow($sprint);
            }

            $service->startSprint($sprint);

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['ok' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/form/complete/{id}', name: 'form_complete', methods: ['GET'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function formComplete(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        Sprint $sprint
    ): Response {
        if ($sprint->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(SprintCompleteType::class, null, [
            'project' => $project,
            'action'  => $this->generateUrl('sprints_complete', [
                'key' => $project->getKey(),
                'id'  => $sprint->getId(),
            ]),
            'method'  => 'POST',
        ]);

        return $this->render('dashboard/sprint/form_complete.html.twig', [
            'form'   => $form->createView(),
            'sprint' => $sprint,
        ]);
    }

    #[Route('/complete/{id}', name: 'complete', methods: ['POST'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function complete(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        Sprint $sprint,
        SprintService $service
    ): JsonResponse {
        if ($sprint->getProject()->getId() !== $project->getId()) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid sprint.'], 404);
        }

        $form = $this->createForm(SprintCompleteType::class, null, ['project' => $project])
            ->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse([
                'ok'   => false,
                'html' => $this->renderView('dashboard/sprint/form_complete.html.twig', [
                    'form'   => $form->createView(),
                    'sprint' => $sprint,
                ]),
            ], 422);
        }

        try {
            $data = $form->getData();
            $next = null;
            if (($data['incompleteStrategy'] ?? 'backlog') === 'next' && !empty($data['nextSprint'])) {
                $candidate = $data['nextSprint'];

                if ($candidate->getProject()->getId() === $project->getId()) {
                    $next = $candidate;
                }
            }

            $service->completeSprint($sprint, $next);

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['ok' => false, 'message' => $e->getMessage()], 400);
        }
    }

    #[Route('/form/tickets/{id}', name: 'form_tickets', methods: ['GET'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function formTickets(
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        Sprint $sprint,
        TicketRepository $ticketRepo
    ): Response {
        if ($sprint->getProject()->getId() !== $project->getId()) {
            throw $this->createNotFoundException();
        }

        $tickets = $ticketRepo->findBySprint($sprint);

        return $this->render('dashboard/sprint/form_tickets.html.twig', [
            'project' => $project,
            'sprint'  => $sprint,
            'tickets' => $tickets,
        ]);
    }

    #[Route('/{id}/ticket/{ticket}/remove', name: 'ticket_remove', methods: ['POST'])]
    #[IsGranted('PROJECT_MANAGER', subject: 'project')]
    public function removeTicket(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        Sprint $sprint,
        Ticket $ticket,
        EntityManagerInterface $em,
    ): JsonResponse {
        if ($sprint->getProject()->getId() !== $project->getId()) {
            return new JsonResponse(['ok' => false, 'message' => 'Sprint not in project.'], 404);
        }

        $ticketProject = $ticket->getStatus()->getWorkflow()->getProject();
        if ($ticketProject->getId() !== $project->getId()) {
            return new JsonResponse(['ok' => false, 'message' => 'Ticket not in this project.'], 400);
        }

        if ($ticket->getSprint()?->getId() !== $sprint->getId()) {
            return new JsonResponse(['ok' => false, 'message' => 'Ticket is not in this sprint.'], 400);
        }

        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('sprint_ticket_remove_' . $ticket->getId(), $token)) {
            return new JsonResponse(['ok' => false, 'message' => 'Invalid CSRF token.'], 400);
        }

        $ticket->setSprint(null);
        $em->flush();

        $tickets = $em->getRepository(Ticket::class)->findBySprint($sprint);
        $html = $this->renderView('dashboard/sprint/form_tickets.html.twig', [
            'project' => $project,
            'sprint'  => $sprint,
            'tickets' => $tickets,
        ]);

        return new JsonResponse(['ok' => true, 'html' => $html]);
    }
}