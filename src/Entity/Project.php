<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use App\Entity\Traits\Uniqueable;
use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;

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

    public function getTitle(): string
    {
        return $this->title;
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
}
