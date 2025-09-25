<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Ticket;
use App\Entity\WorkflowStatus;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

class BoardSearchService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Environment $twig,
    ) {}

    public function renderColumns(Project $project, ?string $search): string
    {
        $search = trim($search);

        [$statuses, $ticketsByStatus] = empty($search)
            ? $this->fullBoardData($project)
            : $this->filteredBoardData($project, $search);

        return $this->twig->render('dashboard/board/board_columns.html.twig', [
            'project'         => $project,
            'statuses'        => $statuses,
            'ticketsByStatus' => $ticketsByStatus,
        ]);
    }

    public function fullBoardData(Project $project): array
    {
        $statuses = $this->em->getRepository(WorkflowStatus::class)->findByProjectWithTickets($project);
        $statuses = $this->em->getRepository(WorkflowStatus::class)->findByProjectOrdered($project);

        if ($project->isScrumProject()) {
            $active = $this->em->getRepository(WorkflowStatus::class)->findActiveForProject($project);
            $ticketsByStatus = [];
            foreach ($statuses as $s) {
                $ticketsByStatus[$s->getId()] = [];
            }

            if ($active) {
                $rows = $this->em->getRepository(WorkflowStatus::class)->findByProjectWithTicketsForSprint($project, $active);
                $ticketsByStatus = [];
                foreach ($rows as $s) {
                    $ticketsByStatus[$s->getId()] = $s->getTickets()->toArray();
                }
            }

            return [$statuses, $ticketsByStatus];
        }

        $rows = $this->em->getRepository(WorkflowStatus::class)->findByProjectWithTickets($project);
        $ticketsByStatus = [];
        foreach ($rows as $s) {
            $ticketsByStatus[$s->getId()] = $s->getTickets()->toArray();
        }

        return [$rows, $ticketsByStatus];
    }

    public function filteredBoardData(Project $project, string $search): array
    {
        $statuses = $this->em->getRepository(WorkflowStatus::class)->findByProjectOrdered($project);

        if ($project->isScrumProject()) {
            $active = $this->em->getRepository(Sprint::class)->findActiveForProject($project);
            $ticketsByStatus = [];
            foreach ($statuses as $s) $ticketsByStatus[$s->getId()] = [];

            if (!$active) {
                return [$statuses, $ticketsByStatus];
            }

            $found = $this->em->getRepository(Ticket::class)->searchByProjectAndTitleInSprint($project, $active, $search);
            foreach ($found as $t) {
                $sid = $t->getStatus()->getId();
                $ticketsByStatus[$sid] ??= [];
                $ticketsByStatus[$sid][] = $t;
            }

            return [$statuses, $ticketsByStatus];
        }

        $found = $this->em->getRepository(Ticket::class)->searchByProjectAndTitle($project, $search);

        $byStatus = [];
        foreach ($found as $t) {
            $sid = $t->getStatus()->getId();
            $byStatus[$sid] ??= [];
            $byStatus[$sid][] = $t;
        }

        if (!$byStatus) {
            return [[], []];
        }

        $statusIds = array_keys($byStatus);
        $matchedStatuses = $this->em->getRepository(WorkflowStatus::class)->getMatchedStatuses($statusIds);

        return [$matchedStatuses, $byStatus];
    }
}
