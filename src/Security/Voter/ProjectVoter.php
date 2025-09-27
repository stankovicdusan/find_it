<?php

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Enum\MemberStatusEnum;
use App\Enum\RoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ProjectVoter extends Voter
{
    public const MEMBER = 'PROJECT_MEMBER';
    public const MANAGER = 'PROJECT_MANAGER';
    public const ADMIN  = 'PROJECT_ADMIN';
    public const MANAGE_SETTINGS = 'PROJECT_MANAGE_SETTINGS';

    public function __construct(private readonly EntityManagerInterface $em) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Project
            && in_array($attribute, [self::MEMBER, self::ADMIN, self::MANAGE_SETTINGS, self::MANAGER], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $project, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $pu = $this->em->getRepository(ProjectUser::class)->findOneByUserAndProject($user, $project);
        if (!$pu || $pu->getStatus() !== MemberStatusEnum::ACTIVE) {
            return false;
        }

        $isAdmin   = $pu->getRole()->getId() === RoleEnum::ADMIN->value;
        $isManager = $pu->getRole()->getId() === RoleEnum::MANAGER->value;

        return match ($attribute) {
            self::MEMBER => true,
            self::ADMIN, self::MANAGE_SETTINGS => $isAdmin,
            self::MANAGER => $isManager || $isAdmin,
            default => false,
        };
    }
}
