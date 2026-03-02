<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
#[ORM\HasLifecycleCallbacks]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 2048)]
    private string $url;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = 'up';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $uptime = '100.00';

    #[ORM\Column(type: Types::INTEGER)]
    private int $responseTime = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastCheckAt = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $checkInterval = 60;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $httpStatusCode = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sslExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $domainExpiresAt = null;

    #[ORM\OneToMany(mappedBy: 'site', targetEntity: Incident::class, cascade: ['persist', 'remove'])]
    private Collection $incidents;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->incidents = new ArrayCollection();
    }

    /**
     * Gets site ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets site name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets site name.
     *
     * @param string $name Site name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets site URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Sets site URL.
     *
     * @param string $url Site URL
     *
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Gets site owner.
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Sets site owner.
     *
     * @param User $user Site owner
     *
     * @return self
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets site status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Sets site status.
     *
     * @param string $status Site status (up, down, warning)
     *
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets site uptime percentage.
     *
     * @return string
     */
    public function getUptime(): string
    {
        return $this->uptime;
    }

    /**
     * Sets site uptime percentage.
     *
     * @param string $uptime Uptime percentage
     *
     * @return self
     */
    public function setUptime(string $uptime): self
    {
        $this->uptime = $uptime;

        return $this;
    }

    /**
     * Gets response time in milliseconds.
     *
     * @return int
     */
    public function getResponseTime(): int
    {
        return $this->responseTime;
    }

    /**
     * Sets response time in milliseconds.
     *
     * @param int $responseTime Response time in ms
     *
     * @return self
     */
    public function setResponseTime(int $responseTime): self
    {
        $this->responseTime = $responseTime;

        return $this;
    }

    /**
     * Gets last check timestamp.
     *
     * @return \DateTimeImmutable|null
     */
    public function getLastCheckAt(): ?\DateTimeImmutable
    {
        return $this->lastCheckAt;
    }

    /**
     * Sets last check timestamp.
     *
     * @param \DateTimeImmutable|null $lastCheckAt Last check timestamp
     *
     * @return self
     */
    public function setLastCheckAt(?\DateTimeImmutable $lastCheckAt): self
    {
        $this->lastCheckAt = $lastCheckAt;

        return $this;
    }

    /**
     * Gets check interval in seconds.
     *
     * @return int
     */
    public function getCheckInterval(): int
    {
        return $this->checkInterval;
    }

    /**
     * Sets check interval in seconds.
     *
     * @param int $checkInterval Check interval in seconds
     *
     * @return self
     */
    public function setCheckInterval(int $checkInterval): self
    {
        $this->checkInterval = $checkInterval;

        return $this;
    }

    /**
     * Gets HTTP status code from last check.
     *
     * @return int|null
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Sets HTTP status code.
     *
     * @param int|null $httpStatusCode HTTP status code
     *
     * @return self
     */
    public function setHttpStatusCode(?int $httpStatusCode): self
    {
        $this->httpStatusCode = $httpStatusCode;

        return $this;
    }

    /**
     * Checks if site monitoring is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Sets site monitoring active status.
     *
     * @param bool $isActive Active status
     *
     * @return self
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Checks if site needs to be checked based on interval.
     *
     * @return bool
     */
    public function needsCheck(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->lastCheckAt === null) {
            return true;
        }

        $nextCheckAt = $this->lastCheckAt->modify("+{$this->checkInterval} seconds");

        return $nextCheckAt <= new \DateTimeImmutable();
    }

    /**
     * Gets SSL certificate expiration date.
     *
     * @return \DateTimeImmutable|null
     */
    public function getSslExpiresAt(): ?\DateTimeImmutable
    {
        return $this->sslExpiresAt;
    }

    /**
     * Sets SSL certificate expiration date.
     *
     * @param \DateTimeImmutable|null $sslExpiresAt SSL expiration date
     *
     * @return self
     */
    public function setSslExpiresAt(?\DateTimeImmutable $sslExpiresAt): self
    {
        $this->sslExpiresAt = $sslExpiresAt;

        return $this;
    }

    /**
     * Gets domain expiration date.
     *
     * @return \DateTimeImmutable|null
     */
    public function getDomainExpiresAt(): ?\DateTimeImmutable
    {
        return $this->domainExpiresAt;
    }

    /**
     * Sets domain expiration date.
     *
     * @param \DateTimeImmutable|null $domainExpiresAt Domain expiration date
     *
     * @return self
     */
    public function setDomainExpiresAt(?\DateTimeImmutable $domainExpiresAt): self
    {
        $this->domainExpiresAt = $domainExpiresAt;

        return $this;
    }

    /**
     * Gets site incidents.
     *
     * @return Collection<int, Incident>
     */
    public function getIncidents(): Collection
    {
        return $this->incidents;
    }

    /**
     * Adds incident to site.
     *
     * @param Incident $incident Incident to add
     *
     * @return self
     */
    public function addIncident(Incident $incident): self
    {
        if (!$this->incidents->contains($incident)) {
            $this->incidents->add($incident);
            $incident->setSite($this);
        }

        return $this;
    }

    /**
     * Removes incident from site.
     *
     * @param Incident $incident Incident to remove
     *
     * @return self
     */
    public function removeIncident(Incident $incident): self
    {
        $this->incidents->removeElement($incident);

        return $this;
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
