<?php

namespace App\Entity;

use App\Entity\Traits\Uniqueable;
use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "workflow_transitions")]
#[ORM\HasLifecycleCallbacks]
class WorkflowTransition
{
    use Uniqueable, Timestampable;

    #[ORM\ManyToOne(targetEntity: WorkflowStatus::class, inversedBy: 'transitions')]
    #[ORM\JoinColumn(nullable: false)]
    private WorkflowStatus $fromStatus;

    #[ORM\ManyToOne(targetEntity: WorkflowStatus::class)]
    #[ORM\JoinColumn(nullable: false)]
    private WorkflowStatus $toStatus;

    public function setFromStatus(WorkflowStatus $fromStatus): void
    {
        $this->fromStatus = $fromStatus;
    }

    public function getFromStatus(): WorkflowStatus
    {
        return $this->fromStatus;
    }

    public function setToStatus(WorkflowStatus $toStatus): void
    {
        $this->toStatus = $toStatus;
    }

    public function getToStatus(): WorkflowStatus
    {
        return $this->toStatus;
    }
}