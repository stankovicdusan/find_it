<?php

namespace App\Service;

use App\Entity\Project;
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

        if (empty($search)) {
            [$statuses, $ticketsByStatus] = $this->fullBoardData($project);
        } else {
            [$statuses, $ticketsByStatus] = $this->filteredBoardData($project, $search);
        }

        return $this->twig->render('dashboard/board/board_columns.html.twig', [
            'project'         => $project,
            'statuses'        => $statuses,
            'ticketsByStatus' => $ticketsByStatus,
        ]);
    }

    public function fullBoardData(Project $project): array
    {
        $statuses = $this->em->getRepository(WorkflowStatus::class)->findByProjectWithTickets($project);

        $ticketsByStatus = [];
        foreach ($statuses as $s) {
            $ticketsByStatus[$s->getId()] = $s->getTickets()->toArray();
        }

        return [$statuses, $ticketsByStatus];
    }

    public function filteredBoardData(Project $project, string $search): array
    {
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
