<?php

namespace App\Entity;

use App\Entity\Traits\Uniqueable;
use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "project_statuses")]
#[ORM\HasLifecycleCallbacks]
class ProjectStatus
{
    use Uniqueable, Timestampable;

    #[ORM\Column]
    private string $title;

    #[ORM\Column(name: '`order`')]
    private int $order;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectStatuses')]
    #[ORM\JoinColumn(nullable: false)]
    private Project $project;

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
}
