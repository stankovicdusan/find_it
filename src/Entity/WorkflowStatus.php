<?php

namespace App\Entity;

use App\Repository\WorkflowStatusRepository;
use App\Entity\Traits\Uniqueable;
use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Validator as MainAssert;

#[ORM\Entity(repositoryClass: WorkflowStatusRepository::class)]
#[ORM\Table(name: "workflow_statuses")]
#[ORM\HasLifecycleCallbacks]
#[MainAssert\WorkflowStatus]
class WorkflowStatus
{
    use Uniqueable, Timestampable;

    #[ORM\Column(length: 100)]
    private string $title;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\Column(name: 'is_final', type: 'boolean')]
    private bool $isFinal = false;

    #[ORM\Column(name: 'is_initial', type: 'boolean')]
    private bool $isInitial = false;

    #[ORM\ManyToOne(targetEntity: Workflow::class, inversedBy: 'statuses')]
    private ?Workflow $workflow = null;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'status', cascade: ['remove'])]
    private Collection $tickets;

    #[ORM\OneToMany(targetEntity: WorkflowTransition::class, mappedBy: 'fromStatus', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $transitions;

    public function __construct()
    {
        $this->tickets     = new ArrayCollection();
        $this->transitions = new ArrayCollection();
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setIsFinal(bool $isFinal): void
    {
        $this->isFinal = $isFinal;
    }

    public function isFinal(): bool
    {
        return $this->isFinal;
    }

    public function setIsInitial(bool $isInitial): void
    {
        $this->isInitial = $isInitial;
    }

    public function isInitial(): bool
    {
        return $this->isInitial;
    }

    public function setWorkflow(Workflow $workflow): void
    {
        $this->workflow = $workflow;
    }

    public function getWorkflow(): Workflow
    {
        return $this->workflow;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    /**
     * @return Collection<int, WorkflowTransition>
     */
    public function getTransitions(): Collection
    {
        return $this->transitions;
    }

    /**
     * @return array<mixed>
     */
    public function getAllowedStatusToIds(): array
    {
        $transitions = [];
        foreach ($this->getTransitions() as $transition) {
            $transitions[] = $transition->getToStatus()->getId();
        }

        return $transitions;
    }
}