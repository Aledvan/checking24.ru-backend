<?php

declare(strict_types=1);

namespace App\DTO\Site;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateSiteRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.name_required')]
        #[Assert\Length(max: 255, maxMessage: 'validation.name_max_length')]
        public string $name,

        #[Assert\NotBlank(message: 'validation.url_required')]
        #[Assert\Url(message: 'validation.url_invalid')]
        #[Assert\Length(max: 2048, maxMessage: 'validation.url_max_length')]
        public string $url,

        #[Assert\Range(min: 30, max: 3600, notInRangeMessage: 'validation.check_interval_range')]
        public int $checkInterval = 60,
    ) {
    }
}
