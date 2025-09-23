<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}