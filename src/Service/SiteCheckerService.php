<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Incident;
use App\Entity\Site;
use App\Entity\SiteCheck;
use App\Entity\Warning;
use App\Repository\IncidentRepository;
use App\Repository\SiteCheckRepository;
use App\Repository\SiteRepository;
use App\Repository\WarningRepository;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SiteCheckerService
{
    private const SSL_WARNING_DAYS = 7;
    private const DOMAIN_WARNING_DAYS = 7;
    private const REQUEST_TIMEOUT = 30;

    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly IncidentRepository $incidentRepository,
        private readonly WarningRepository $warningRepository,
        private readonly SiteCheckRepository $siteCheckRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Checks a single site.
     *
     * @param Site $site Site to check
     *
     * @return void
     */
    public function checkSite(Site $site): void
    {
        $this->logger->debug('Starting site check', [
            'site_id' => $site->getId(),
            'site_url' => $site->getUrl(),
        ]);

        $startTime = microtime(true);
        $httpCode = null;
        $errorMessage = null;

        try {
            $response = $this->httpClient->request('GET', $site->getUrl(), [
                'timeout' => self::REQUEST_TIMEOUT,
                'verify_peer' => true,
                'verify_host' => true,
            ]);

            $httpCode = $response->getStatusCode();
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $httpCode = 0;

            $this->logger->warning('Site check failed with error', [
                'site_id' => $site->getId(),
                'site_url' => $site->getUrl(),
                'error' => $errorMessage,
            ]);
        }

        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        // Determine if site is up
        $isUp = $httpCode >= 200 && $httpCode < 400;

        // Update site metrics
        $site->setHttpStatusCode($httpCode);
        $site->setResponseTime($responseTime);
        $site->setLastCheckAt(new \DateTimeImmutable());

        // Update status based on HTTP code
        $previousStatus = $site->getStatus();
        $newStatus = $this->determineStatus($httpCode);
        $site->setStatus($newStatus);

        // Update uptime
        $this->updateUptime($site, $isUp);

        // Save check history
        $this->saveCheckHistory($site, $isUp, $responseTime, $httpCode, $errorMessage);

        // Handle incidents
        $this->handleIncident($site, $previousStatus, $newStatus, $httpCode, $errorMessage);

        // Check SSL certificate
        $this->checkSslCertificate($site);

        // Check warnings
        $this->checkWarnings($site);

        $this->siteRepository->save($site, true);

        $this->logger->info('Site check finished', [
            'site_id' => $site->getId(),
            'site_url' => $site->getUrl(),
            'http_code' => $httpCode,
            'response_time_ms' => $responseTime,
            'status' => $newStatus,
            'previous_status' => $previousStatus,
        ]);
    }

    /**
     * Saves check result to history.
     *
     * @param Site $site Site entity
     * @param bool $isUp Whether site is up
     * @param int $responseTime Response time in ms
     * @param int $httpCode HTTP status code
     * @param string|null $errorMessage Error message
     *
     * @return void
     */
    private function saveCheckHistory(Site $site, bool $isUp, int $responseTime, int $httpCode, ?string $errorMessage): void
    {
        $siteCheck = new SiteCheck();
        $siteCheck->setSite($site);
        $siteCheck->setIsUp($isUp);
        $siteCheck->setResponseTime($responseTime);
        $siteCheck->setHttpStatusCode($httpCode > 0 ? $httpCode : null);
        $siteCheck->setErrorMessage($errorMessage);
        $siteCheck->setCheckedAt(new \DateTimeImmutable());

        $this->siteCheckRepository->save($siteCheck, true);
    }

    /**
     * Gets all sites that need to be checked.
     *
     * @return Site[]
     */
    public function getSitesNeedingCheck(): array
    {
        return $this->siteRepository->findSitesNeedingCheck();
    }

    /**
     * Determines site status based on HTTP code.
     *
     * @param int $httpCode HTTP status code
     *
     * @return string
     */
    private function determineStatus(int $httpCode): string
    {
        if ($httpCode === 0) {
            return 'down';
        }

        if ($httpCode >= 200 && $httpCode < 400) {
            return 'up';
        }

        return 'down';
    }

    /**
     * Updates site uptime percentage.
     *
     * @param Site $site Site entity
     * @param bool $isUp Whether site is up
     *
     * @return void
     */
    private function updateUptime(Site $site, bool $isUp): void
    {
        $currentUptime = (float) $site->getUptime();

        // Simple moving average (weight recent checks more)
        $weight = 0.01;
        $newUptime = $isUp
            ? $currentUptime + ($weight * (100 - $currentUptime))
            : $currentUptime - ($weight * $currentUptime);

        $site->setUptime(number_format(max(0, min(100, $newUptime)), 2, '.', ''));
    }

    /**
     * Handles incident creation/resolution.
     *
     * @param Site $site Site entity
     * @param string $previousStatus Previous status
     * @param string $newStatus New status
     * @param int $httpCode HTTP status code
     * @param string|null $errorMessage Error message
     *
     * @return void
     */
    private function handleIncident(Site $site, string $previousStatus, string $newStatus, int $httpCode, ?string $errorMessage): void
    {
        // Site went down - create incident
        if ($previousStatus !== 'down' && $newStatus === 'down') {
            $incident = new Incident();
            $incident->setSite($site);
            $incident->setStartedAt(new \DateTimeImmutable());

            if ($httpCode === 0) {
                $incident->setCause($errorMessage ?? 'Connection failed');
            } else {
                $incident->setCause("HTTP {$httpCode} error");
            }

            $this->incidentRepository->save($incident, true);
        }

        // Site came back up - resolve incident
        if ($previousStatus === 'down' && $newStatus === 'up') {
            $activeIncidents = $this->incidentRepository->findActiveByUser($site->getUser());

            foreach ($activeIncidents as $incident) {
                if ($incident->getSite()->getId() === $site->getId()) {
                    $incident->setResolvedAt(new \DateTimeImmutable());
                    $this->incidentRepository->save($incident, true);
                }
            }
        }
    }

    /**
     * Checks SSL certificate expiration.
     *
     * @param Site $site Site entity
     *
     * @return void
     */
    private function checkSslCertificate(Site $site): void
    {
        $url = $site->getUrl();

        if (!str_starts_with($url, 'https://')) {
            return;
        }

        try {
            $parsed = parse_url($url);
            $host = $parsed['host'] ?? null;

            if ($host === null) {
                return;
            }

            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $socket = @stream_socket_client(
                "ssl://{$host}:443",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($socket === false) {
                return;
            }

            $params = stream_context_get_params($socket);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;

            if ($cert === null) {
                fclose($socket);
                return;
            }

            $certInfo = openssl_x509_parse($cert);
            fclose($socket);

            if ($certInfo === false || !isset($certInfo['validTo_time_t'])) {
                return;
            }

            $expiresAt = new \DateTimeImmutable('@' . $certInfo['validTo_time_t']);
            $site->setSslExpiresAt($expiresAt);
        } catch (\Throwable) {
            // Silently ignore SSL check errors
        }
    }

    /**
     * Checks and creates warnings for SSL/domain expiration.
     *
     * @param Site $site Site entity
     *
     * @return void
     */
    private function checkWarnings(Site $site): void
    {
        $now = new \DateTimeImmutable();
        $sslWarningDate = $now->modify('+' . self::SSL_WARNING_DAYS . ' days');
        $domainWarningDate = $now->modify('+' . self::DOMAIN_WARNING_DAYS . ' days');

        // Check SSL warning
        $sslExpires = $site->getSslExpiresAt();
        if ($sslExpires !== null && $sslExpires <= $sslWarningDate && $sslExpires > $now) {
            $this->createOrUpdateWarning($site, Warning::TYPE_SSL, $sslExpires);
        }

        // Check domain warning
        $domainExpires = $site->getDomainExpiresAt();
        if ($domainExpires !== null && $domainExpires <= $domainWarningDate && $domainExpires > $now) {
            $this->createOrUpdateWarning($site, Warning::TYPE_DOMAIN, $domainExpires);
        }
    }

    /**
     * Creates or updates a warning.
     *
     * @param Site $site Site entity
     * @param string $type Warning type
     * @param \DateTimeImmutable $expiresAt Expiration date
     *
     * @return void
     */
    private function createOrUpdateWarning(Site $site, string $type, \DateTimeImmutable $expiresAt): void
    {
        // Check if warning already exists
        $existingWarnings = $this->warningRepository->findBy([
            'site' => $site,
            'type' => $type,
            'isResolved' => false,
        ]);

        if (count($existingWarnings) > 0) {
            $warning = $existingWarnings[0];
            $warning->setExpiresAt($expiresAt);
        } else {
            $warning = new Warning();
            $warning->setSite($site);
            $warning->setType($type);
            $warning->setExpiresAt($expiresAt);
        }

        $this->warningRepository->save($warning, true);
    }
}
