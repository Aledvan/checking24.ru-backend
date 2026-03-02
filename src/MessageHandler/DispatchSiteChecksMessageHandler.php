<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckSiteMessage;
use App\Message\DispatchSiteChecksMessage;
use App\Repository\SiteRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class DispatchSiteChecksMessageHandler
{
    public function __construct(
        private SiteRepository $siteRepository,
        private MessageBusInterface $messageBus,
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

        foreach ($sites as $site) {
            $this->messageBus->dispatch(new CheckSiteMessage($site->getId()));
        }
    }
}
