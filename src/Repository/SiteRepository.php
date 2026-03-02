<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Site;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Site::class);
    }

    /**
     * Saves site to database.
     *
     * @param Site $site Site entity
     * @param bool $flush Whether to flush the entity manager
     *
     * @return void
     */
    public function save(Site $site, bool $flush = false): void
    {
        $this->getEntityManager()->persist($site);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes site from database.
     *
     * @param Site $site Site entity
     * @param bool $flush Whether to flush the entity manager
     *
     * @return void
     */
    public function remove(Site $site, bool $flush = false): void
    {
        $this->getEntityManager()->remove($site);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds all sites belonging to a user.
     *
     * @param User $user Site owner
     *
     * @return Site[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * Finds site by ID and user.
     *
     * @param int $id Site ID
     * @param User $user Site owner
     *
     * @return Site|null
     */
    public function findByIdAndUser(int $id, User $user): ?Site
    {
        return $this->findOneBy(['id' => $id, 'user' => $user]);
    }

    /**
     * Finds all active sites that need to be checked.
     *
     * @return Site[]
     */
    public function findSitesNeedingCheck(): array
    {
        $sites = $this->findBy(['isActive' => true]);

        return array_filter($sites, fn(Site $site) => $site->needsCheck());
    }

    /**
     * Finds all active sites.
     *
     * @return Site[]
     */
    public function findAllActive(): array
    {
        return $this->findBy(['isActive' => true]);
    }
}
