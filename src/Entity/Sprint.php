<?php

namespace App\Entity;

use App\Entity\Traits\Uniqueable;
use App\Enum\SprintStateEnum;
use App\Repository\SprintRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: SprintRepository::class)]
#[ORM\Table(name: "sprints")]
class Sprint
{
    use Uniqueable;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $goal = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $plannedStartAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $plannedEndAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(length: 20, enumType: SprintStateEnum::class)]
    private SprintStateEnum $state = SprintStateEnum::PLANNED;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'sprint')]
    private Collection $tickets;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPlannedStartAt(): \DateTimeImmutable
    {
        return $this->plannedStartAt;
    }

    public function setPlannedStartAt(\DateTimeImmutable $plannedStartAt): void
    {
        $this->plannedStartAt = $plannedStartAt;
    }

    public function getPlannedEndAt(): \DateTimeImmutable
    {
        return $this->plannedEndAt;
    }

    public function setPlannedEndAt(\DateTimeImmutable $plannedEndAt): void
    {
        $this->plannedEndAt = $plannedEndAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(\DateTimeImmutable $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getGoal(): ?string
    {
        return $this->goal;
    }

    public function setGoal(string $goal): void
    {
        $this->goal = $goal;
    }

    public function getState(): SprintStateEnum
    {
        return $this->state;
    }

    public function setState(SprintStateEnum $state): void
    {
        $this->state = $state;
    }

    public function getTickets(): Collection
    {
        return $this->tickets;
    }
}
