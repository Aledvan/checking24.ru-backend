<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Site\CreateSiteRequest;
use App\DTO\Site\UpdateSiteRequest;
use App\Entity\Site;
use App\Entity\User;
use App\Entity\Warning;
use App\Message\CheckSiteMessage;
use App\Repository\SiteRepository;
use Symfony\Component\Messenger\MessageBusInterface;

class SiteService
{
    private const WARNING_DAYS_THRESHOLD = 7;

    public function __construct(
        private readonly SiteRepository $siteRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Gets all sites for a user.
     *
     * @param User $user Site owner
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSitesForUser(User $user): array
    {
        $sites = $this->siteRepository->findByUser($user);

        return array_map(fn(Site $site) => $this->formatSite($site), $sites);
    }

    /**
     * Gets a single site by ID for a user.
     *
     * @param int $id Site ID
     * @param User $user Site owner
     *
     * @return array<string, mixed>|null
     */
    public function getSiteForUser(int $id, User $user): ?array
    {
        $site = $this->siteRepository->findByIdAndUser($id, $user);

        if ($site === null) {
            return null;
        }

        return $this->formatSite($site);
    }

    /**
     * Creates a new site.
     *
     * @param CreateSiteRequest $request Site data
     * @param User $user Site owner
     *
     * @return array<string, mixed>
     */
    public function createSite(CreateSiteRequest $request, User $user): array
    {
        $site = new Site();
        $site->setName($request->name);
        $site->setUrl($request->url);
        $site->setCheckInterval($request->checkInterval);
        $site->setUser($user);

        $this->siteRepository->save($site, true);

        // Dispatch immediate check for the new site
        $this->messageBus->dispatch(new CheckSiteMessage($site->getId()));

        return $this->formatSite($site);
    }

    /**
     * Updates an existing site.
     *
     * @param int $id Site ID
     * @param UpdateSiteRequest $request Site data
     * @param User $user Site owner
     *
     * @return array<string, mixed>|null
     */
    public function updateSite(int $id, UpdateSiteRequest $request, User $user): ?array
    {
        $site = $this->siteRepository->findByIdAndUser($id, $user);

        if ($site === null) {
            return null;
        }

        $site->setName($request->name);
        $site->setUrl($request->url);
        $site->setCheckInterval($request->checkInterval);
        $site->setIsActive($request->isActive);

        $this->siteRepository->save($site, true);

        return $this->formatSite($site);
    }

    /**
     * Deletes a site.
     *
     * @param int $id Site ID
     * @param User $user Site owner
     *
     * @return bool
     */
    public function deleteSite(int $id, User $user): bool
    {
        $site = $this->siteRepository->findByIdAndUser($id, $user);

        if ($site === null) {
            return false;
        }

        $this->siteRepository->remove($site, true);

        return true;
    }

    /**
     * Calculates site status based on HTTP code and expiration dates.
     *
     * @param Site $site Site entity
     *
     * @return string
     */
    private function calculateStatus(Site $site): string
    {
        $httpCode = $site->getHttpStatusCode();

        // If site has HTTP error
        if ($httpCode !== null && $httpCode >= 400) {
            return 'down';
        }

        // Check SSL and domain expiration warnings
        $now = new \DateTimeImmutable();
        $warningDate = $now->modify('+' . self::WARNING_DAYS_THRESHOLD . ' days');

        $sslExpires = $site->getSslExpiresAt();
        $domainExpires = $site->getDomainExpiresAt();

        if ($sslExpires !== null && $sslExpires <= $now) {
            return 'down';
        }

        if ($domainExpires !== null && $domainExpires <= $now) {
            return 'down';
        }

        if (($sslExpires !== null && $sslExpires <= $warningDate) ||
            ($domainExpires !== null && $domainExpires <= $warningDate)) {
            return 'warning';
        }

        return 'up';
    }

    /**
     * Formats site entity to array.
     *
     * @param Site $site Site entity
     *
     * @return array<string, mixed>
     */
    private function formatSite(Site $site): array
    {
        return [
            'id' => $site->getId(),
            'name' => $site->getName(),
            'url' => $site->getUrl(),
            'status' => $this->calculateStatus($site),
            'httpStatusCode' => $site->getHttpStatusCode(),
            'uptime' => $site->getUptime(),
            'responseTime' => $site->getResponseTime(),
            'checkInterval' => $site->getCheckInterval(),
            'isActive' => $site->isActive(),
            'lastCheckAt' => $site->getLastCheckAt()?->format('Y-m-d H:i:s'),
            'sslExpiresAt' => $site->getSslExpiresAt()?->format('Y-m-d'),
            'domainExpiresAt' => $site->getDomainExpiresAt()?->format('Y-m-d'),
            'createdAt' => $site->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
