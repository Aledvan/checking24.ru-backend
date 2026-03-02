<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;

class JwtService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl,
    ) {
    }

    public function createToken(User $user): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ]));

        $now = time();
        $payload = $this->base64UrlEncode(json_encode([
            'iat' => $now,
            'exp' => $now + $this->ttl,
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payloadData = json_decode($this->base64UrlDecode($payload), true);

        if ($payloadData === null) {
            return null;
        }

        if (!isset($payloadData['exp']) || $payloadData['exp'] < time()) {
            return null;
        }

        return $payloadData;
    }

    public function getUserIdFromToken(string $token): ?int
    {
        $payload = $this->validateToken($token);

        return $payload['sub'] ?? null;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
