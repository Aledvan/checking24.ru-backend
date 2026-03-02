<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\IncidentRepository;
use App\Repository\SiteCheckRepository;
use App\Repository\SiteRepository;

class DashboardService
{
    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly IncidentRepository $incidentRepository,
        private readonly SiteCheckRepository $siteCheckRepository,
    ) {
    }

    /**
     * Gets dashboard statistics for a user.
     *
     * @param User $user Site owner
     *
     * @return array<string, mixed>
     */
    public function getStatistics(User $user): array
    {
        $sites = $this->siteRepository->findByUser($user);
        $activeIncidents = $this->incidentRepository->findActiveByUser($user);

        // Count sites
        $totalSites = count($sites);

        // Count active sites (sites without active incidents)
        $siteIdsWithIncidents = [];
        foreach ($activeIncidents as $incident) {
            $siteIdsWithIncidents[$incident->getSite()->getId()] = true;
        }

        $activeSites = 0;
        foreach ($sites as $site) {
            if ($site->isActive() && !isset($siteIdsWithIncidents[$site->getId()])) {
                $activeSites++;
            }
        }

        // Average uptime for 30 days
        $averageUptime = $this->siteCheckRepository->getAverageUptimeForDays($user, 30);

        // Active incidents count
        $activeIncidentsCount = count($activeIncidents);

        // Average response time for last hour
        $avgResponseTime = $this->siteCheckRepository->getAverageResponseTimeLastHour($user);

        // Chart data
        $uptimeChartData = $this->siteCheckRepository->getHourlyUptimeForToday($user);
        $responseTimeChartData = $this->siteCheckRepository->getHourlyResponseTimeForToday($user);

        // Fill in missing hours for charts
        $uptimeChartData = $this->fillMissingHours($uptimeChartData, 'uptime', 100.0);
        $responseTimeChartData = $this->fillMissingHours($responseTimeChartData, 'ms', 0);

        // Add current time label to last entry
        if (!empty($uptimeChartData)) {
            $uptimeChartData[count($uptimeChartData) - 1]['time'] = 'Сейчас';
        }
        if (!empty($responseTimeChartData)) {
            $responseTimeChartData[count($responseTimeChartData) - 1]['time'] = 'Сейчас';
        }

        return [
            'totalSites' => $totalSites,
            'activeSites' => $activeSites,
            'averageUptime' => number_format($averageUptime, 2),
            'activeIncidents' => $activeIncidentsCount,
            'avgResponseTime' => $avgResponseTime !== null ? (int) round($avgResponseTime) : null,
            'uptimeChart' => $uptimeChartData,
            'responseTimeChart' => $responseTimeChartData,
        ];
    }

    /**
     * Fills in missing hours in chart data.
     *
     * @param array $data Existing data
     * @param string $valueKey Key for the value field
     * @param mixed $defaultValue Default value for missing hours
     *
     * @return array
     */
    private function fillMissingHours(array $data, string $valueKey, mixed $defaultValue): array
    {
        $now = new \DateTimeImmutable();
        $currentHour = (int) $now->format('H');

        $existingHours = [];
        foreach ($data as $entry) {
            $hour = substr($entry['time'], 0, 2);
            $existingHours[$hour] = $entry;
        }

        $result = [];
        for ($h = 0; $h <= $currentHour; $h++) {
            $hourStr = str_pad((string) $h, 2, '0', STR_PAD_LEFT);
            $timeLabel = "{$hourStr}:00";

            if (isset($existingHours[$hourStr])) {
                $result[] = $existingHours[$hourStr];
            } else {
                $result[] = [
                    'time' => $timeLabel,
                    $valueKey => $defaultValue,
                ];
            }
        }

        return $result;
    }
}
