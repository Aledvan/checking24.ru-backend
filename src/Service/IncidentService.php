<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Incident;
use App\Entity\User;
use App\Repository\IncidentRepository;

class IncidentService
{
    public function __construct(
        private readonly IncidentRepository $incidentRepository,
    ) {
    }

    /**
     * Gets all incidents for a user.
     *
     * @param User $user Site owner
     *
     * @return array<string, mixed>
     */
    public function getIncidentsForUser(User $user): array
    {
        $incidents = $this->incidentRepository->findByUser($user);

        return array_map(fn(Incident $incident) => $this->formatIncident($incident), $incidents);
    }

    /**
     * Gets active (unresolved) incidents for a user.
     *
     * @param User $user Site owner
     *
     * @return array<string, mixed>
     */
    public function getActiveIncidentsForUser(User $user): array
    {
        $incidents = $this->incidentRepository->findActiveByUser($user);

        return array_map(fn(Incident $incident) => $this->formatIncident($incident), $incidents);
    }

    /**
     * Gets resolved incidents for a user.
     *
     * @param User $user Site owner
     *
     * @return array<string, mixed>
     */
    public function getResolvedIncidentsForUser(User $user): array
    {
        $incidents = $this->incidentRepository->findResolvedByUser($user);

        return array_map(fn(Incident $incident) => $this->formatIncident($incident), $incidents);
    }

    /**
     * Formats incident entity to array.
     *
     * @param Incident $incident Incident entity
     *
     * @return array<string, mixed>
     */
    private function formatIncident(Incident $incident): array
    {
        $site = $incident->getSite();

        return [
            'id' => $incident->getId(),
            'siteId' => $site->getId(),
            'siteName' => $site->getName(),
            'url' => $site->getUrl(),
            'startedAt' => $incident->getStartedAt()->format('Y-m-d H:i'),
            'resolvedAt' => $incident->getResolvedAt()?->format('Y-m-d H:i'),
            'duration' => $incident->getDurationFormatted(),
            'cause' => $incident->getCause(),
        ];
    }
}
