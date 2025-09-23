<?php

// src/Security/Voter/ProjectVoter.php
namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\ProjectMember;
use App\Entity\User;
use App\Enum\MemberStatusEnum;
use App\Enum\ProjectRoleEnum;
use App\Repository\ProjectMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProjectVoter extends Voter
{
    public const MEMBER = 'PROJECT_MEMBER';
    public const ADMIN  = 'PROJECT_ADMIN';
    public const MANAGE_SETTINGS = 'PROJECT_MANAGE_SETTINGS';

    public function __construct(private readonly EntityManagerInterface $em) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Project
            && in_array($attribute, [self::MEMBER, self::ADMIN, self::MANAGE_SETTINGS], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $project, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        $pm = $this->em->getRepository(ProjectMember::class)->findOneByUserAndProject($user, $project);
        if (!$pm || $pm->getStatus() !== MemberStatusEnum::ACTIVE) return false;

        return match ($attribute) {
            self::MEMBER => true,
            self::ADMIN, self::MANAGE_SETTINGS => $pm->getRole() === ProjectRoleEnum::ADMIN,
            default => false,
        };
    }
}
