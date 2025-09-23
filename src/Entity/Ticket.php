<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use App\Enum\PriorityEnum;
use App\Enum\IssueTypeEnum;
use App\Entity\Traits\Uniqueable;
use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use App\Entity\Traits\Deleteable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
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
    private int $indexNumber;

    #[ORM\Column(type: 'string', enumType: PriorityEnum::class)]
    private PriorityEnum $priority;

    #[ORM\Column(name: '`order`')]
    private int $order;

    #[ORM\ManyToOne(targetEntity: WorkflowStatus::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private WorkflowStatus $status;

    #[ORM\ManyToOne(targetEntity: IssueType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private IssueType $issueType;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_to_id', referencedColumnName: 'id')]
    private ?User $assignedTo = null;

    #[ORM\OneToMany(targetEntity: TicketComment::class, mappedBy: 'ticket', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

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

    public function getIndexNumber(): int
    {
        return $this->indexNumber;
    }

    public function setIndexNumber(int $indexNumber): void
    {
        $this->indexNumber = $indexNumber;
    }

    public function getPriority(): PriorityEnum 
    {
        return $this->priority;
    }

    public function setPriority(PriorityEnum $priority): void
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

    public function getStatus(): WorkflowStatus
    {
        return $this->status;
    }

    public function setStatus(WorkflowStatus $status): void
    {
        $this->status = $status;
    }

    public function setIssueType(IssueType $issueType): void
    {
        $this->issueType = $issueType;
    }

    public function getIssueType(): IssueType
    {
        return $this->issueType;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(User $assignedTo): void
    {
        $this->assignedTo = $assignedTo;
    }

    /**
     * @return Collection<int, TicketComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getColor(): string
    {
        if (IssueTypeEnum::BUG->value === $this->getIssueType()->getTitle()) {
            return "#dc3545";
        } else {
            return "#0d6efd";
        }
    }

    public function getAssignedToName(): string
    {
        if (null === $this->getAssignedTo()) {
            return 'Unassigned';
        } else {
            return $this->getAssignedTo()->getFullName();
        }
    }

    public function getPriorityBadge(): string
    {
        return match ($this->getPriority()) {
            PriorityEnum::LOW => 'bg-secondary',
            PriorityEnum::MEDIUM => 'bg-warning text-dark',
            PriorityEnum::HIGH => 'bg-danger',
        };
    }
}
