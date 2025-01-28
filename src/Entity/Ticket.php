<?php

namespace App\Entity;

use App\Enum\PriorityEnum;
use App\Entity\Traits\Uniqueable;
use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use App\Entity\Traits\Deleteable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "tickets")]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    use Uniqueable, Blameable, Timestampable, Deleteable;

    #[ORM\Column]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'index_number')]
    private string $indexNumber;

    #[ORM\Column(type: 'string', enumType: PriorityEnum::class)]
    private string $priority;

    #[ORM\Column(name: '`order`')]
    private int $order;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_to_id', referencedColumnName: 'id')]
    private ?User $assignedTo = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIndexNumber(): string
    {
        return $this->indexNumber;
    }

    public function setIndexNumber(string $indexNumber): void
    {
        $this->indexNumber = $indexNumber;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): void
    {
        $this->priority = $priority;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(string $order): void
    {
        $this->order = $order;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(User $user): void
    {
        $this->user = $user;
    }
}
