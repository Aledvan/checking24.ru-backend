<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Site;
use App\Entity\SiteCheck;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SiteCheckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteCheck::class);
    }

    public function save(SiteCheck $siteCheck, bool $flush = false): void
    {
        $this->getEntityManager()->persist($siteCheck);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SiteCheck $siteCheck, bool $flush = false): void
    {
        $this->getEntityManager()->remove($siteCheck);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Gets average response time for user's sites in the last hour.
     */
    public function getAverageResponseTimeLastHour(User $user): ?float
    {
        $oneHourAgo = new \DateTimeImmutable('-1 hour');

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT AVG(c.response_time) as avg_response_time
            FROM site_checks c
            INNER JOIN sites s ON c.site_id = s.id
            WHERE s.user_id = :userId
            AND c.checked_at >= :oneHourAgo
            AND c.is_up = 1
        ";

        $result = $conn->prepare($sql)->executeQuery([
            'userId' => $user->getId(),
            'oneHourAgo' => $oneHourAgo->format('Y-m-d H:i:s'),
        ])->fetchOne();

        return $result !== null && $result !== false ? (float) $result : null;
    }

    /**
     * Gets uptime percentage for user's sites over the specified days.
     */
    public function getAverageUptimeForDays(User $user, int $days = 30): float
    {
        $startDate = new \DateTimeImmutable("-{$days} days");

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                COUNT(*) as total_checks,
                SUM(CASE WHEN c.is_up = 1 THEN 1 ELSE 0 END) as up_checks
            FROM site_checks c
            INNER JOIN sites s ON c.site_id = s.id
            WHERE s.user_id = :userId
            AND c.checked_at >= :startDate
        ";

        $result = $conn->prepare($sql)->executeQuery([
            'userId' => $user->getId(),
            'startDate' => $startDate->format('Y-m-d H:i:s'),
        ])->fetchAssociative();

        $totalChecks = (int) ($result['total_checks'] ?? 0);
        $upChecks = (int) ($result['up_checks'] ?? 0);

        if ($totalChecks === 0) {
            return 100.0;
        }

        return round(($upChecks / $totalChecks) * 100, 2);
    }

    /**
     * Gets hourly uptime data for today (for chart).
     */
    public function getHourlyUptimeForToday(User $user): array
    {
        $startOfDay = new \DateTimeImmutable('today midnight');
        $now = new \DateTimeImmutable();

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                DATE_FORMAT(c.checked_at, '%H:00') as hour,
                COUNT(*) as total_checks,
                SUM(CASE WHEN c.is_up = 1 THEN 1 ELSE 0 END) as up_checks
            FROM site_checks c
            INNER JOIN sites s ON c.site_id = s.id
            WHERE s.user_id = :userId
            AND c.checked_at >= :startOfDay
            AND c.checked_at <= :now
            GROUP BY DATE_FORMAT(c.checked_at, '%H:00')
            ORDER BY hour ASC
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'userId' => $user->getId(),
            'startOfDay' => $startOfDay->format('Y-m-d H:i:s'),
            'now' => $now->format('Y-m-d H:i:s'),
        ]);

        $data = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $totalChecks = (int) $row['total_checks'];
            $upChecks = (int) $row['up_checks'];
            $uptime = $totalChecks > 0 ? round(($upChecks / $totalChecks) * 100, 2) : 100.0;

            $data[] = [
                'time' => $row['hour'],
                'uptime' => $uptime,
            ];
        }

        return $data;
    }

    /**
     * Gets hourly response time data for today (for chart).
     */
    public function getHourlyResponseTimeForToday(User $user): array
    {
        $startOfDay = new \DateTimeImmutable('today midnight');
        $now = new \DateTimeImmutable();

        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT
                DATE_FORMAT(c.checked_at, '%H:00') as hour,
                AVG(c.response_time) as avg_response_time
            FROM site_checks c
            INNER JOIN sites s ON c.site_id = s.id
            WHERE s.user_id = :userId
            AND c.checked_at >= :startOfDay
            AND c.checked_at <= :now
            AND c.is_up = 1
            GROUP BY DATE_FORMAT(c.checked_at, '%H:00')
            ORDER BY hour ASC
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'userId' => $user->getId(),
            'startOfDay' => $startOfDay->format('Y-m-d H:i:s'),
            'now' => $now->format('Y-m-d H:i:s'),
        ]);

        $data = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $data[] = [
                'time' => $row['hour'],
                'ms' => (int) round((float) $row['avg_response_time']),
            ];
        }

        return $data;
    }

    /**
     * Deletes old check records (older than specified days).
     */
    public function deleteOlderThan(int $days): int
    {
        $cutoffDate = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('c')
            ->delete()
            ->where('c.checkedAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute();
    }
}
