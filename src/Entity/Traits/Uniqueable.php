<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait Uniqueable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    public function getId(): int
    {
        return $this->id;
    }
}
