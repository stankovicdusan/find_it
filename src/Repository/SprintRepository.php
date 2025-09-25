<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Sprint;
use App\Enum\SprintStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SprintRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sprint::class);
    }

    public function findActiveForProject(Project $project): ?Sprint
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.project = :p')
            ->setParameter('p', $project)
            ->andWhere('s.state = :st')
            ->setParameter('st', SprintStateEnum::ACTIVE)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    public function findPlannedForProject(Project $project): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.project = :p')
            ->setParameter('p', $project)
            ->andWhere('s.state = :st')
            ->setParameter('st', SprintStateEnum::PLANNED)
            ->orderBy('s.plannedStartAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function findCompletedForProject(Project $project, int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.project = :p')
            ->setParameter('p', $project)
            ->andWhere('s.state = :st')
            ->setParameter('st', SprintStateEnum::COMPLETED)
            ->orderBy('s.completedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}