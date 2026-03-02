<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateProfileRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.name_required')]
        #[Assert\Length(max: 100, maxMessage: 'validation.name_max_length')]
        public string $name,

        #[Assert\NotBlank(message: 'validation.email_required')]
        #[Assert\Email(message: 'validation.email_invalid')]
        public string $email,
    ) {
    }
}
