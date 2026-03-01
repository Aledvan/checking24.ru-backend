<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.email_required')]
        #[Assert\Email(message: 'validation.email_invalid')]
        public string $email,

        #[Assert\NotBlank(message: 'validation.password_required')]
        #[Assert\Length(min: 6, minMessage: 'validation.password_min_length')]
        public string $password,

        #[Assert\Length(max: 100, maxMessage: 'validation.name_max_length')]
        public ?string $name = null,
    ) {
    }
}
