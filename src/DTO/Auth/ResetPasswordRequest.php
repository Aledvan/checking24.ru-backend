<?php

declare(strict_types=1);

namespace App\DTO\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ResetPasswordRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'validation.token_required')]
        public string $token,

        #[Assert\NotBlank(message: 'validation.password_required')]
        #[Assert\Length(min: 6, minMessage: 'validation.password_min_length')]
        public string $password,
    ) {
    }
}
