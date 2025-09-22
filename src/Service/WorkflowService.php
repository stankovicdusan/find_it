<?php

namespace App\Service;

use App\Enum\DefaultWorkflowStatusEnum;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;

class WorkflowService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {    
    }

    public function createDefaultWorkflow(Entity\Project $project): void
    {
        $workflow = new Entity\Workflow();
        $workflow->setTitle($project->getTitle() . ' Workflow');
        $workflow->setProject($project);

        $order = 1;
        foreach (DefaultWorkflowStatusEnum::cases() as $statusEnum) {
            $status = new Entity\WorkflowStatus();
            $status->setTitle($statusEnum->value);
            $status->setSortOrder($order++);
            $status->setWorkflow($workflow);
            $status->setIsInitial(DefaultWorkflowStatusEnum::BACKLOG === $statusEnum);

            $this->em->persist($status);

            $statuses[] = $status;
        }

        $project->setWorkflow($workflow);

        $this->em->persist($workflow);
        $this->em->flush();

        foreach ($statuses as $from) {
            foreach ($statuses as $to) {
                if ($from !== $to) {
                    $transition = new Entity\WorkflowTransition();
                    $transition->setFromStatus($from);
                    $transition->setToStatus($to);

                    $this->em->persist($transition);
                }
            }
        }

        $this->em->flush();
    }
}
