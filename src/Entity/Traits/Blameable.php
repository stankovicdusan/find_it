<?php

namespace App\Entity\Traits;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait Blameable
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "created_by", nullable: false)]
    private User $createdBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "updated_by")]
    private ?User $updatedBy;

    public function setCreatedBy(User $user): void
    {
        $this->createdBy = $user;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(User $user): void
    {
        $this->updatedBy = $user;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }
}
