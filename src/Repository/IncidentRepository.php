<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Incident;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IncidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Incident::class);
    }

    /**
     * Saves incident to database.
     *
     * @param Incident $incident Incident entity
     * @param bool $flush Whether to flush the entity manager
     *
     * @return void
     */
    public function save(Incident $incident, bool $flush = false): void
    {
        $this->getEntityManager()->persist($incident);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes incident from database.
     *
     * @param Incident $incident Incident entity
     * @param bool $flush Whether to flush the entity manager
     *
     * @return void
     */
    public function remove(Incident $incident, bool $flush = false): void
    {
        $this->getEntityManager()->remove($incident);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds all incidents for sites belonging to a user.
     *
     * @param User $user Site owner
     *
     * @return Incident[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.site', 's')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds active (unresolved) incidents for a user.
     *
     * @param User $user Site owner
     *
     * @return Incident[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.site', 's')
            ->where('s.user = :user')
            ->andWhere('i.resolvedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('i.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds resolved incidents for a user.
     *
     * @param User $user Site owner
     *
     * @return Incident[]
     */
    public function findResolvedByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.site', 's')
            ->where('s.user = :user')
            ->andWhere('i.resolvedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('i.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
