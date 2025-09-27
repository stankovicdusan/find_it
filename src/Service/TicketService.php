<?php

namespace App\Service;

use App\Entity;
use App\Entity\Ticket;
use App\Entity\User;
use App\Entity\Workflow;
use App\Entity\WorkflowStatus;
use Doctrine\ORM\EntityManagerInterface;

class TicketService 
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function createTicket(Entity\Ticket $ticket, Entity\Project $project, Entity\User $user): void
    {
        $ticket->setCreatedBy($user);
        $ticket->setUpdatedBy($user);

        $ticket->setIndexNumber($this->generateIndexNumber($project));

        $initialWorkflowStatus = $this->em->getRepository(Entity\WorkflowStatus::class)->findInitialStatusByProject($project);
        $ticket->setStatus($initialWorkflowStatus);

        $ticketOrder = $this->em->getRepository(Entity\Ticket::class)->getNextOrderBasedOnStatus($ticket);
        $ticket->setOrder($ticketOrder);

        $this->em->persist($ticket);
        $this->em->flush();
    }

    public function canMoveTo(?WorkflowStatus $from, WorkflowStatus $to): bool
    {
        if ($from->getId() === $to->getId()) {
            return true;
        }

        if ($from->getWorkflow()->getId() !== $to->getWorkflow()->getId()) {
            return false;
        }

        foreach ($from->getTransitions() as $transition) {
            if ($transition->getToStatus()->getId() === $to->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int> $toOrder
     */
    public function moveTicket(Ticket $ticket, WorkflowStatus $to, array $toOrder = []): void
    {
        $from = $ticket->getStatus();
        $sameColumn = $from->getId() === $to->getId();

        if (!$sameColumn) {
            $ticket->setStatus($to);
        }

        if (!empty($toOrder)) {
            $this->reorderColumn($to, $toOrder);
        } else {
            if (!$sameColumn) {
                $ticket->setOrder($this->em->getRepository(Ticket::class)->getNextOrderBasedOnStatus($ticket));
            }
        }

        $this->em->flush();
    }

    public function changeAssignee(Ticket $ticket, ?User $user): void
    {
        $ticket->setAssignedTo($user);

        $this->em->flush();
    }

    /**
     * @param array<int> $orderedIds
     */
    private function reorderColumn(WorkflowStatus $status, array $orderedIds): void
    {
        if (empty($orderedIds)) {
            return;
        }

        $tickets = $this->em->getRepository(Ticket::class)->findBy(['status' => $status, 'id' => $orderedIds]);

        $byId = [];
        foreach ($tickets as $t) {
            $byId[$t->getId()] = $t;
        }

        $order = 1;
        foreach ($orderedIds as $id) {
            if (!isset($byId[$id])) {
                continue;
            }
            $byId[$id]->setOrder($order++);
        }
    }

    private function generateIndexNumber(Entity\Project $project): int
    {
        $lastTicket = $this->em->getRepository(Entity\Ticket::class)->getLastTicketIndexNumberPerProject($project);

        return ++$lastTicket;
    }
}