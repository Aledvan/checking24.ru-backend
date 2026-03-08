<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WarningRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WarningRepository::class)]
#[ORM\Table(name: 'warnings')]
#[ORM\HasLifecycleCallbacks]
class Warning
{
    public const TYPE_SSL = 'ssl';
    public const TYPE_DOMAIN = 'domain';

    public const DAYS_BEFORE_WARNING = 7;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Site $site;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $type;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastNotifiedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isResolved = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Gets warning ID.
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
     * Gets warning type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets warning type.
     *
     * @param string $type Warning type (ssl or domain)
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets expiration date.
     *
     * @return \DateTimeImmutable
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Sets expiration date.
     *
     * @param \DateTimeImmutable $expiresAt Expiration date
     *
     * @return self
     */
    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Gets last notification timestamp.
     *
     * @return \DateTimeImmutable|null
     */
    public function getLastNotifiedAt(): ?\DateTimeImmutable
    {
        return $this->lastNotifiedAt;
    }

    /**
     * Sets last notification timestamp.
     *
     * @param \DateTimeImmutable|null $lastNotifiedAt Last notification time
     *
     * @return self
     */
    public function setLastNotifiedAt(?\DateTimeImmutable $lastNotifiedAt): self
    {
        $this->lastNotifiedAt = $lastNotifiedAt;

        return $this;
    }

    /**
     * Checks if warning is resolved.
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    /**
     * Sets resolved status.
     *
     * @param bool $isResolved Resolved status
     *
     * @return self
     */
    public function setIsResolved(bool $isResolved): self
    {
        $this->isResolved = $isResolved;

        return $this;
    }

    /**
     * Calculates days left until expiration.
     *
     * @return int
     */
    public function getDaysLeft(): int
    {
        $now = new \DateTimeImmutable();
        $diff = $now->diff($this->expiresAt);

        if ($diff->invert) {
            return 0;
        }

        return $diff->days;
    }

    /**
     * Checks if notification should be sent today.
     *
     * @return bool
     */
    public function shouldNotifyToday(): bool
    {
        $daysLeft = $this->getDaysLeft();

        if ($daysLeft > self::DAYS_BEFORE_WARNING || $this->isResolved) {
            return false;
        }

        if ($this->lastNotifiedAt === null) {
            return true;
        }

        $today = new \DateTimeImmutable('today');
        $lastNotified = $this->lastNotifiedAt->setTime(0, 0, 0);

        return $lastNotified < $today;
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
