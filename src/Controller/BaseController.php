<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BaseController extends AbstractController
{
    protected function getLoggedInUser(): User
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('You must be logged in to access this resource.');
        }

        return $user;
    }
}
