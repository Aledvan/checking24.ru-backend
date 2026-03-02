<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Symfony\Component\HttpFoundation\Cookie;

class TokenService
{
    public function __construct(
        private readonly JwtService $jwtService,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly int $refreshTokenTtl,
    ) {
    }

    public function createTokenPair(User $user, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        $accessToken = $this->jwtService->createToken($user);
        $refreshToken = RefreshToken::create($user, $this->refreshTokenTtl, $userAgent, $ipAddress);

        $this->refreshTokenRepository->save($refreshToken);

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken->getToken(),
            'expiresIn' => $this->jwtService->getTtl(),
        ];
    }

    public function refreshTokens(string $refreshTokenValue, ?string $userAgent = null, ?string $ipAddress = null): ?array
    {
        $refreshToken = $this->refreshTokenRepository->findValidByToken($refreshTokenValue);

        if ($refreshToken === null) {
            return null;
        }

        $user = $refreshToken->getUser();

        // Remove old refresh token
        $this->refreshTokenRepository->remove($refreshToken);

        // Create new token pair
        return $this->createTokenPair($user, $userAgent, $ipAddress);
    }

    public function revokeRefreshToken(string $refreshTokenValue): bool
    {
        $refreshToken = $this->refreshTokenRepository->findByToken($refreshTokenValue);

        if ($refreshToken === null) {
            return false;
        }

        $this->refreshTokenRepository->remove($refreshToken);

        return true;
    }

    public function revokeAllUserTokens(User $user): void
    {
        $this->refreshTokenRepository->removeAllForUser($user);
    }

    public function createRefreshTokenCookie(string $refreshToken): Cookie
    {
        return Cookie::create('refresh_token')
            ->withValue($refreshToken)
            ->withExpires(new \DateTimeImmutable("+{$this->refreshTokenTtl} seconds"))
            ->withPath('/api/v1/auth')
            ->withSecure(false) // Set to true in production with HTTPS
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }

    public function createLogoutCookie(): Cookie
    {
        return Cookie::create('refresh_token')
            ->withValue('')
            ->withExpires(new \DateTimeImmutable('-1 hour'))
            ->withPath('/api/v1/auth')
            ->withSecure(false)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);
    }

    public function validateAccessToken(string $token): ?array
    {
        return $this->jwtService->validateToken($token);
    }
}
