<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckSiteMessage;
use App\Repository\SiteRepository;
use App\Service\SiteCheckerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckSiteMessageHandler
{
    public function __construct(
        private SiteRepository $siteRepository,
        private SiteCheckerService $siteCheckerService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Handles site check message.
     *
     * @param CheckSiteMessage $message Message to handle
     *
     * @return void
     */
    public function __invoke(CheckSiteMessage $message): void
    {
        $siteId = $message->getSiteId();

        $this->logger->info('Processing site check from queue', [
            'site_id' => $siteId,
        ]);

        $site = $this->siteRepository->find($siteId);

        if ($site === null) {
            $this->logger->warning('Site not found, skipping check', [
                'site_id' => $siteId,
            ]);
            return;
        }

        if (!$site->isActive()) {
            $this->logger->info('Site is inactive, skipping check', [
                'site_id' => $siteId,
                'site_url' => $site->getUrl(),
            ]);
            return;
        }

        $this->siteCheckerService->checkSite($site);

        $this->logger->info('Site check completed from queue', [
            'site_id' => $siteId,
            'site_url' => $site->getUrl(),
        ]);
    }
}
