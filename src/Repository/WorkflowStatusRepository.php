<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Sprint;
use App\Entity\Ticket;
use App\Entity\Workflow;
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

    public function checkStatusTitleUniqueness(Workflow $workflow, string $title, ?int $statusId = null): bool
    {
        $qb = $this->createQueryBuilder('ws')
            ->where('ws.workflow = :wf')
            ->andWhere('LOWER(TRIM(ws.title)) = :title')
            ->setParameter('wf', $workflow)
            ->setParameter('title', $title)
            ->setMaxResults(1);

        // if status exists, exclude it from the check.
        if (null !== $statusId) {
            $qb->andWhere('ws.id <> :id')
                ->setParameter('id', $statusId);
        }

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    public function getMatchedStatuses(array $statusIds): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.id IN (:ids)')
            ->setParameter('ids', $statusIds)
            ->orderBy('s.sortOrder', 'ASC')
            ->getQuery()->getResult();
    }

    public function findByProjectOrdered(Project $project): array
    {
        return $this->createQueryBuilder('ws')
            ->leftJoin('ws.workflow', 'w')
            ->leftJoin('w.project', 'p')
            ->where('p = :project')->setParameter('project', $project)
            ->orderBy('ws.sortOrder', 'ASC')
            ->getQuery()->getResult();
    }

    public function findByProjectWithTicketsForSprint(Project $project, Sprint $sprint): array
    {
        return $this->createQueryBuilder('ws')
            ->leftJoin('ws.workflow', 'w')
            ->leftJoin('w.project', 'p')
            ->leftJoin('ws.tickets', 't', 'WITH', 't.sprint = :sprint')
            ->addSelect('t')
            ->where('p = :project')
            ->setParameter('project', $project)
            ->setParameter('sprint', $sprint)
            ->orderBy('ws.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextOrderBasedOnWorkflow(Workflow $workflow): int
    {
        $lastOrder = $this->createQueryBuilder('ws')
            ->select('MAX(ws.sortOrder)')
            ->where('ws.workflow = :workflow')
            ->setParameter('workflow', $workflow)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return (int) $lastOrder + 1;
    }
}
