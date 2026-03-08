<?php

declare(strict_types=1);

namespace App\Message;

final readonly class CheckSiteMessage
{
    public function __construct(
        public int $siteId,
    ) {
    }

    /**
     * Gets site ID.
     *
     * @return int
     */
    public function getSiteId(): int
    {
        return $this->siteId;
    }
}
