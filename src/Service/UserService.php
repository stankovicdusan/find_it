<?php

namespace App\Service;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserService
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {    
    }

    public function getCurrentUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        return $user instanceof UserInterface ? $user : null;
    }
}
