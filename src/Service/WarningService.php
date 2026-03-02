<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Warning;
use App\Repository\WarningRepository;

class WarningService
{
    public function __construct(
        private readonly WarningRepository $warningRepository,
    ) {
    }

    /**
     * Gets all active warnings for a user.
     *
     * @param User $user Site owner
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWarningsForUser(User $user): array
    {
        $warnings = $this->warningRepository->findActiveByUser($user);

        return array_map(fn(Warning $warning) => $this->formatWarning($warning), $warnings);
    }

    /**
     * Gets warnings pending notification for a user.
     *
     * @param User $user Site owner
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendingWarningsForUser(User $user): array
    {
        $warnings = $this->warningRepository->findPendingNotifications($user);

        return array_map(fn(Warning $warning) => $this->formatWarning($warning), $warnings);
    }

    /**
     * Formats warning entity to array.
     *
     * @param Warning $warning Warning entity
     *
     * @return array<string, mixed>
     */
    private function formatWarning(Warning $warning): array
    {
        $site = $warning->getSite();

        return [
            'id' => $warning->getId(),
            'siteId' => $site->getId(),
            'siteName' => $site->getName(),
            'type' => $warning->getType(),
            'expiresAt' => $warning->getExpiresAt()->format('Y-m-d'),
            'daysLeft' => $warning->getDaysLeft(),
            'lastNotifiedAt' => $warning->getLastNotifiedAt()?->format('Y-m-d H:i'),
        ];
    }
}
