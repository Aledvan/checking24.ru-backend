<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CheckSiteMessage;
use App\Repository\SiteRepository;
use App\Service\SiteCheckerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckSiteMessageHandler
{
    public function __construct(
        private SiteRepository $siteRepository,
        private SiteCheckerService $siteCheckerService,
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
        $site = $this->siteRepository->find($message->getSiteId());

        if ($site === null) {
            return;
        }

        if (!$site->isActive()) {
            return;
        }

        $this->siteCheckerService->checkSite($site);
    }
}
