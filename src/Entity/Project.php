<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use App\Entity\Traits\Uniqueable;
use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\EventListener\OnCreate\ProjectWorkflowListener;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: "projects")]
#[ORM\HasLifecycleCallbacks]
class Project
{
    use Uniqueable, Blameable, Timestampable;

    #[ORM\Column]
    private string $title;

    #[ORM\Column(name: "`key`")]
    private string $key;

    #[ORM\ManyToOne(targetEntity: ProjectTemplate::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ProjectTemplate $template;

    #[ORM\OneToOne(inversedBy: 'project', cascade: ['persist', 'remove'])]
    private ?Workflow $workflow = null;

    public function __construct()
    {
        $this->tickets = new ArrayCollection();
    }

    public function getTitle(): string
    {
        return ucfirst(strtolower($this->title));
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getTemplate(): ProjectTemplate
    {
        return $this->template;
    }

    public function setTemplate(ProjectTemplate $template): void
    {
        $this->template = $template;
    }

    public function getWorkflow(): ?Workflow
    {
        return $this->workflow;
    }

    public function setWorkflow(?Workflow $workflow): void
    {
        $this->workflow = $workflow;
    }
}
