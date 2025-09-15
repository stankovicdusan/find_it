<?php

namespace App\Service;

use App\Entity;
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

    private function generateIndexNumber(Entity\Project $project): int
    {
        $lastTicket = $this->em->getRepository(Entity\Ticket::class)->getLastTicketIndexNumberPerProject($project);

        return ++$lastTicket;
    }
}