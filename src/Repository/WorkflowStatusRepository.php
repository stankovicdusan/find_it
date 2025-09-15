<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\WorkflowStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkflowStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowStatus::class);
    }

    public function findByProjectWithTickets(Project $project): array
    {
        return $this->createQueryBuilder('ws')
            ->leftJoin('ws.workflow', 'w')
            ->leftJoin('w.project', 'p')
            ->leftJoin('ws.tickets', 't')
            ->addSelect('t')
            ->where('p = :project')
            ->setParameter('project', $project)
            ->orderBy('ws.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInitialStatusByProject(Project $project): ?WorkflowStatus
    {
        return $this->createQueryBuilder('ws')
            ->leftJoin('ws.workflow', 'w')
            ->leftJoin('w.project', 'p')
            ->where('p = :project')
            ->andWhere('ws.isInitial = true')
            ->setParameter('project', $project)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
