<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.email_required')]
        #[Assert\Email(message: 'validation.email_invalid')]
        public string $email,

        #[Assert\NotBlank(message: 'validation.password_required')]
        public string $password,
    ) {
    }
}
