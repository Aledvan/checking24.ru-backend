<?php

declare(strict_types=1);

namespace App\DTO\Auth;

final readonly class AuthResponse
{
    public function __construct(
        public int $id,
        public string $email,
        public ?string $name,
        public array $roles,
    ) {
    }

    /**
     * Converts DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'roles' => $this->roles,
        ];
    }
}
