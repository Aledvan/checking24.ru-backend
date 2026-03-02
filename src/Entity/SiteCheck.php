<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteCheckRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteCheckRepository::class)]
#[ORM\Table(name: 'site_checks')]
#[ORM\Index(columns: ['site_id', 'checked_at'], name: 'idx_site_checks_site_checked')]
#[ORM\Index(columns: ['checked_at'], name: 'idx_site_checks_checked_at')]
class SiteCheck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Site $site;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isUp;

    #[ORM\Column(type: Types::INTEGER)]
    private int $responseTime;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $httpStatusCode = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $checkedAt;

    public function __construct()
    {
        $this->checkedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function setSite(Site $site): self
    {
        $this->site = $site;
        return $this;
    }

    public function isUp(): bool
    {
        return $this->isUp;
    }

    public function setIsUp(bool $isUp): self
    {
        $this->isUp = $isUp;
        return $this;
    }

    public function getResponseTime(): int
    {
        return $this->responseTime;
    }

    public function setResponseTime(int $responseTime): self
    {
        $this->responseTime = $responseTime;
        return $this;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public function setHttpStatusCode(?int $httpStatusCode): self
    {
        $this->httpStatusCode = $httpStatusCode;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getCheckedAt(): \DateTimeImmutable
    {
        return $this->checkedAt;
    }

    public function setCheckedAt(\DateTimeImmutable $checkedAt): self
    {
        $this->checkedAt = $checkedAt;
        return $this;
    }
}
