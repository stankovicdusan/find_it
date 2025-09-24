<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\ProjectUser;
use App\Entity\User;
use App\Enum\MemberStatusEnum;
use App\Enum\ProjectRoleEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectUser::class);
    }

    public function searchByProject(Project $p, string $q): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.user','u')
            ->addSelect('u')
            ->andWhere('m.project = :p')
            ->setParameter('p',$p)
            ->orderBy('m.id','DESC');

        if ('' !== $q) {
            $qb->andWhere("LOWER(m.email) LIKE :q OR LOWER(u.email) LIKE :q OR LOWER(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) LIKE :q")
                ->setParameter('q','%'.mb_strtolower($q).'%');
        }

        return $qb->getQuery()->getResult();
    }

    public function memberExists(Project $project, string $email): bool
    {
        return (bool)$this->createQueryBuilder('m')
            ->select('1')
            ->andWhere('m.project = :project AND m.email = :email')
            ->setParameter('project', $project)
            ->setParameter('email', $email)
            ->getQuery()->getOneOrNullResult();
    }

    public function countAdmins(Project $project): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.project = :p AND m.role = :r')
            ->setParameter('p', $project)
            ->setParameter('r', ProjectRoleEnum::ADMIN)
            ->getQuery()->getSingleScalarResult();
    }

    public function countPendingForEmail(string $email): int
    {
        return (int) $this->createQueryBuilder('pm')
            ->select('COUNT(pm.id)')
            ->andWhere('LOWER(pm.email) = :e')
            ->andWhere('pm.status = :s')
            ->setParameter('e', $email)
            ->setParameter('s', MemberStatusEnum::INVITED)
            ->getQuery()->getSingleScalarResult();
    }

    public function findPendingForEmail(string $email): array
    {
        return $this->createQueryBuilder('pm')
            ->leftJoin('pm.project', 'p')
            ->addSelect('p')
            ->andWhere('LOWER(pm.email) = :e')
            ->andWhere('pm.status = :s')
            ->setParameter('e', $email)
            ->setParameter('s', MemberStatusEnum::INVITED)
            ->orderBy('p.title', 'ASC')
            ->getQuery()->getResult();
    }

    public function findOneByUserAndProject(User $user, Project $project): ?ProjectUser
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.project = :project')
            ->setParameter('user', $user)
            ->setParameter('project', $project)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
