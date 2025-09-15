<?php

namespace App\Entity;

use App\Entity\Traits\Uniqueable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: "workflows")]
class Workflow
{
    use Uniqueable;

    #[ORM\Column(length: 100)]
    private string $title;

    #[ORM\OneToMany(targetEntity: WorkflowStatus::class, mappedBy: 'workflow')]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    private Collection $statuses;

    #[ORM\OneToOne(mappedBy: 'workflow')]
    private ?Project $project = null;

    public function __construct()
    {
        $this->statuses = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return ucfirst(strtolower($this->title));
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): void
    {
        $this->project = $project;
    }

    /**
     * @return Collection<int, WorkflowStatus>
     */
    public function getStatuses(): Collection
    {
        return $this->statuses;
    }
}