<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait Deleteable
{
    #[ORM\Column(name: "deleted_at", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    public function setDeletedAt(\DateTimeInterface $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }
}