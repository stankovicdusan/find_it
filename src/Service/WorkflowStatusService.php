<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\WorkflowStatus;
use App\Entity\WorkflowTransition;
use Doctrine\ORM\EntityManagerInterface;

class WorkflowStatusService
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    public function newFor(Project $project): WorkflowStatus
    {
        $status = new WorkflowStatus();
        $status->setWorkflow($project->getWorkflow());
        $status->setSortOrder($this->nextSortOrder($project));
        return $status;
    }

    public function save(WorkflowStatus $status, array $toStatusIds): void
    {
        if ($status->isInitial()) {
            foreach ($status->getWorkflow()->getStatuses() as $s) {
                if ($s !== $status && $s->isInitial()) {
                    $s->setIsInitial(false);
                }
            }
        }

        foreach ($status->getTransitions() as $existing) {
            $this->em->remove($existing);
        }

        $byId = [];
        foreach ($status->getWorkflow()->getStatuses() as $s) {
            $byId[$s->getId()] = $s;
        }

        foreach ($toStatusIds as $id) {
            if (!isset($byId[$id])) { 
                continue; 
            }

            $to = $byId[$id];
            if ($to === $status) { 
                continue; 
            }

            $t = new WorkflowTransition();

            $t->setFromStatus($status);
            $t->setToStatus($to);

            $this->em->persist($t);
        }

        if (null === $status->getId()) {
            $this->em->persist($status);
        }

        $this->em->flush();
    }

    public function deleteIfEmpty(WorkflowStatus $status): void
    {
        // Guard: no tickets bound
        if ($status->getTickets()->count() > 0) {
            throw new \RuntimeException('Cannot delete: this status has tickets.');
        }

        // Remove outgoing transitions
        foreach ($status->getTransitions() as $t) {
            $this->em->remove($t);
        }

        $this->em->remove($status);
        $this->em->flush();
    }

    private function nextSortOrder(Project $project): int
    {
        $max = 0;
        foreach ($project->getWorkflow()->getStatuses() as $s) {
            $max = max($max, (int) $s->getSortOrder());
        }

        return $max + 1;
    }
}