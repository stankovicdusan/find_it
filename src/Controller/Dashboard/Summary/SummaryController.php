<?php

namespace App\Controller\Dashboard\Summary;

use App\Controller\BaseController;
use App\Entity\Project;
use App\Entity\Ticket;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('PROJECT_MEMBER', subject: 'project')]
class SummaryController extends BaseController
{
    #[Route('/dashboard/{key}/summary', name: 'dashboard_summary', methods: ['GET'])]
    public function __invoke(
        Request $request,
        #[MapEntity(mapping: ['key' => 'key'])] Project $project,
        EntityManagerInterface $em,
    ): Response {
        $days  = max(1, min(30, (int) $request->query->get('days', 7)));
        $since = new \DateTimeImmutable(sprintf('-%d days', $days));

        $ticketRepo = $em->getRepository(Ticket::class);

        $tickets   = $ticketRepo->findByProject($project);
        $total     = \count($tickets);
        $updatedN  = $ticketRepo->countUpdatedSince($project, $since);
        $createdN  = $ticketRepo->countCreatedSince($project, $since);

        dump($tickets);

        $statusCounts = [];
        foreach ($project->getWorkflow()->getStatuses() as $s) {
            $statusCounts[$s->getTitle()] = 0;
        }

        $typeCounts = [];
        $workload   = [];
        $unassigned = 0;

        foreach ($tickets as $ticket) {
            $statusCounts[$ticket->getStatus()->getTitle()]++;
            $assignee = $ticket->getAssignedTo()?->getFullName() ?? 'Unassigned';
            $workload[$assignee] = ($workload[$assignee] ?? 0) + 1;

            if (!$ticket->getAssignedTo()) {
                $unassigned++;
            }

            if ($ticket->getIssueType()) {
                $title = $ticket->getIssueType()->getTitle();
                $typeCounts[$title] = ($typeCounts[$title] ?? 0) + 1;
            }
        }

        arsort($workload);

        return $this->render('dashboard/summary/index.html.twig', [
            'project'      => $project,
            'days'         => $days,
            'kpis'         => [
                'total'      => $total,
                'updatedN'   => $updatedN,
                'createdN'   => $createdN,
                'unassigned' => $unassigned,
            ],
            'statusCounts' => $statusCounts,
            'typeCounts'   => $typeCounts,
            'workload'     => $workload,
            'activeMenu'   => 'summary',
        ]);
    }
}
