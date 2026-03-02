<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\IncidentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncidentRepository::class)]
#[ORM\Table(name: 'incidents')]
#[ORM\HasLifecycleCallbacks]
class Incident
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Site::class, inversedBy: 'incidents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Site $site;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $cause;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->startedAt = new \DateTimeImmutable();
    }

    /**
     * Gets incident ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets associated site.
     *
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * Sets associated site.
     *
     * @param Site $site Site entity
     *
     * @return self
     */
    public function setSite(Site $site): self
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Gets incident start time.
     *
     * @return \DateTimeImmutable
     */
    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * Sets incident start time.
     *
     * @param \DateTimeImmutable $startedAt Incident start time
     *
     * @return self
     */
    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * Gets incident resolution time.
     *
     * @return \DateTimeImmutable|null
     */
    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    /**
     * Sets incident resolution time.
     *
     * @param \DateTimeImmutable|null $resolvedAt Resolution time
     *
     * @return self
     */
    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    /**
     * Gets incident cause description.
     *
     * @return string
     */
    public function getCause(): string
    {
        return $this->cause;
    }

    /**
     * Sets incident cause description.
     *
     * @param string $cause Cause description
     *
     * @return self
     */
    public function setCause(string $cause): self
    {
        $this->cause = $cause;

        return $this;
    }

    /**
     * Checks if incident is resolved.
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->resolvedAt !== null;
    }

    /**
     * Calculates incident duration.
     *
     * @return \DateInterval|null
     */
    public function getDuration(): ?\DateInterval
    {
        if ($this->resolvedAt === null) {
            return null;
        }

        return $this->startedAt->diff($this->resolvedAt);
    }

    /**
     * Gets formatted duration string.
     *
     * @return string|null
     */
    public function getDurationFormatted(): ?string
    {
        $duration = $this->getDuration();

        if ($duration === null) {
            return null;
        }

        $parts = [];

        if ($duration->d > 0) {
            $parts[] = $duration->d . ' д';
        }

        if ($duration->h > 0) {
            $parts[] = $duration->h . ' ч';
        }

        if ($duration->i > 0) {
            $parts[] = $duration->i . ' мин';
        }

        if (empty($parts)) {
            return '< 1 мин';
        }

        return implode(' ', $parts);
    }

    /**
     * Gets creation timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Gets last update timestamp.
     *
     * @return \DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Updates timestamp on entity update.
     *
     * @return void
     */
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
