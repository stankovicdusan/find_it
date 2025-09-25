<?php

namespace App\Repository;

use App\Entity\Sprint;
use App\Entity\Ticket;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function getLastTicketIndexNumberPerProject(Project $project): int
    {
        $indexNumber = $this->createQueryBuilder('t')
            ->select('t.indexNumber')
            ->join('t.status', 's')
            ->join('s.workflow', 'w')
            ->join('w.project', 'p')
            ->where('p = :project')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('project', $project)
            ->getQuery()
            ->getOneOrNullResult();

        return $indexNumber['indexNumber'] ?? 0;
    }

    public function getNextOrderBasedOnStatus(Ticket $ticket): int
    {
        $lastOrder = $this->createQueryBuilder('t')
            ->select('MAX(t.order)')
            ->where('t.status = :status')
            ->setParameter('status', $ticket->getStatus())
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return (int) $lastOrder + 1;
    }

    public function searchByProjectAndTitle(Project $project, string $q): array
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.status', 's')
            ->join('s.workflow', 'w')
            ->join('w.project', 'p')
            ->andWhere('p = :project')
            ->setParameter('project', $project)
            ->andWhere('LOWER(t.title) LIKE :q')
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('t.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countUpdatedSince(Project $project, \DateTimeImmutable $since): int
    {
        return (int) $this->qbForProject($project)
            ->select('COUNT(t.id)')
            ->andWhere('t.updatedAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()->getSingleScalarResult();
    }

    public function countCreatedSince(Project $project, \DateTimeImmutable $since): int
    {
        return (int) $this->qbForProject($project)
            ->select('COUNT(t.id)')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()->getSingleScalarResult();
    }

    public function findByProject(Project $project): array
    {
        return $this->qbForProject($project)
            ->leftJoin('t.issueType', 'it')->addSelect('it')
            ->leftJoin('t.assignedTo', 'u')->addSelect('u')
            ->getQuery()->getResult();
    }

    public function findBacklogForProject(Project $project): array
    {
        return $this->qbForProject($project)
            ->leftJoin('t.sprint', 'sp')
            ->andWhere('sp IS NULL')
            ->leftJoin('t.issueType','it')->addSelect('it')
            ->leftJoin('t.assignedTo','u')->addSelect('u')
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findBySprint(Sprint $sprint): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.sprint = :s')
            ->setParameter('s', $sprint)
            ->leftJoin('t.issueType','it')
            ->addSelect('it')
            ->leftJoin('t.assignedTo','u')
            ->addSelect('u')
            ->getQuery()->getResult();
    }

    public function searchByProjectAndTitleInSprint(Project $project, Sprint $sprint, string $q): array
    {
        $q = trim($q);
        if ($q === '') return [];

        return $this->qbForProject($project)
            ->andWhere('t.sprint = :sprint')->setParameter('sprint', $sprint)
            ->andWhere('LOWER(t.title) LIKE :q')->setParameter('q', '%'.mb_strtolower($q).'%')
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function qbForProject(Project $project): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.status', 'st')
            ->innerJoin('st.workflow', 'wf')
            ->innerJoin('wf.project', 'p')
            ->andWhere('p = :project')
            ->setParameter('project', $project);
    }
}