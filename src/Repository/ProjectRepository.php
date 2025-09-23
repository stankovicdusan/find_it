<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\MemberStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.members', 'pm')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', MemberStatusEnum::ACTIVE)
            ->addSelect('pm')
            ->distinct()
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}