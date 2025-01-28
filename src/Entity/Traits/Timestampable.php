<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

trait Timestampable
{
    #[ORM\Column(name: "created_at", type: "datetime")]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: "updated_at", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function updateTimestamps(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onUpdate(PreUpdateEventArgs $args): void
    {
        if (count($args->getEntityChangeSet())) {
            $this->updatedAt = new \DateTime();
        }
    }
}