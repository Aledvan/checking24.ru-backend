<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\Warning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WarningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Warning::class);
    }

    /**
     * Saves warning to database.
     *
     * @param Warning $warning Warning entity
     * @param bool $flush Whether to flush the entity manager
     *
     * @return void
     */
    public function save(Warning $warning, bool $flush = false): void
    {
        $this->getEntityManager()->persist($warning);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes warning from database.
     *
     * @param Warning $warning Warning entity
     * @param bool $flush Whether to flush the entity manager
     *
     * @return void
     */
    public function remove(Warning $warning, bool $flush = false): void
    {
        $this->getEntityManager()->remove($warning);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds all active warnings for a user.
     *
     * @param User $user Site owner
     *
     * @return Warning[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->innerJoin('w.site', 's')
            ->where('s.user = :user')
            ->andWhere('w.isResolved = false')
            ->andWhere('w.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('w.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds warnings that should trigger notification.
     *
     * @param User $user Site owner
     *
     * @return Warning[]
     */
    public function findPendingNotifications(User $user): array
    {
        $warningDate = new \DateTimeImmutable('+' . Warning::DAYS_BEFORE_WARNING . ' days');

        return $this->createQueryBuilder('w')
            ->innerJoin('w.site', 's')
            ->where('s.user = :user')
            ->andWhere('w.isResolved = false')
            ->andWhere('w.expiresAt <= :warningDate')
            ->andWhere('w.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('warningDate', $warningDate)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('w.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds all warnings for a user.
     *
     * @param User $user Site owner
     *
     * @return Warning[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->innerJoin('w.site', 's')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
