<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckSiteMessage;
use App\Message\DispatchSiteChecksMessage;
use App\Repository\SiteRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class DispatchSiteChecksMessageHandler
{
    public function __construct(
        private SiteRepository $siteRepository,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Dispatches site check messages for all sites needing check.
     *
     * @param DispatchSiteChecksMessage $message Message to handle
     *
     * @return void
     */
    public function __invoke(DispatchSiteChecksMessage $message): void
    {
        $sites = $this->siteRepository->findSitesNeedingCheck();

        $this->logger->info('Periodic check dispatcher started', [
            'sites_count' => count($sites),
        ]);

        foreach ($sites as $site) {
            $this->logger->debug('Dispatching periodic check to queue', [
                'site_id' => $site->getId(),
                'site_url' => $site->getUrl(),
            ]);

            $this->messageBus->dispatch(new CheckSiteMessage($site->getId()));
        }

        $this->logger->info('Periodic check dispatcher completed', [
            'sites_dispatched' => count($sites),
        ]);
    }
}
