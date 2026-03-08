<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Auth\ChangePasswordRequest;
use App\DTO\Auth\LoginRequest;
use App\DTO\Auth\RegisterRequest;
use App\DTO\Auth\ResetPasswordRequest;
use App\DTO\Auth\UpdateProfileRequest;
use App\Entity\User;
use App\Exception\AuthException;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenService $tokenService,
        // private readonly EmailService $emailService, // TODO: включить после установки symfony/mailer
    ) {
    }

    public function register(RegisterRequest $request, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        if ($this->userRepository->existsByEmail($request->email)) {
            throw AuthException::userExists();
        }

        $user = new User();
        $user->setEmail($request->email);
        $user->setName($request->name);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $request->password);
        $user->setPassword($hashedPassword);

        // Generate email verification token
        $user->generateEmailVerificationToken();

        $this->userRepository->save($user, true);

        // TODO: Send verification email after installing symfony/mailer
        // $this->emailService->sendEmailVerification($user);

        // Create tokens
        $tokens = $this->tokenService->createTokenPair($user, $userAgent, $ipAddress);

        return [
            'user' => $this->createUserData($user),
            'tokens' => $tokens,
        ];
    }

    public function login(LoginRequest $request, ?string $userAgent = null, ?string $ipAddress = null): array
    {
        $user = $this->userRepository->findByEmail($request->email);

        if ($user === null) {
            throw AuthException::invalidCredentials();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw AuthException::invalidCredentials();
        }

        // Create tokens
        $tokens = $this->tokenService->createTokenPair($user, $userAgent, $ipAddress);

        return [
            'user' => $this->createUserData($user),
            'tokens' => $tokens,
        ];
    }

    public function logout(string $refreshToken): void
    {
        $this->tokenService->revokeRefreshToken($refreshToken);
    }

    public function refresh(string $refreshToken, ?string $userAgent = null, ?string $ipAddress = null): ?array
    {
        $tokens = $this->tokenService->refreshTokens($refreshToken, $userAgent, $ipAddress);

        if ($tokens === null) {
            return null;
        }

        return $tokens;
    }

    public function verifyEmail(string $token): bool
    {
        $user = $this->userRepository->findOneBy(['emailVerificationToken' => $token]);

        if ($user === null || !$user->isEmailVerificationTokenValid()) {
            return false;
        }

        $user->setIsEmailVerified(true);
        $user->clearEmailVerificationToken();

        $this->userRepository->save($user, true);

        return true;
    }

    public function resendVerificationEmail(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null || $user->isEmailVerified()) {
            return false;
        }

        $user->generateEmailVerificationToken();
        $this->userRepository->save($user, true);

        // TODO: Send verification email after installing symfony/mailer
        // $this->emailService->sendEmailVerification($user);

        return true;
    }

    public function requestPasswordReset(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            // Return true to prevent email enumeration
            return true;
        }

        $user->generatePasswordResetToken();
        $this->userRepository->save($user, true);

        // TODO: Send password reset email after installing symfony/mailer
        // $this->emailService->sendPasswordReset($user);

        return true;
    }

    public function resetPassword(ResetPasswordRequest $request): bool
    {
        $user = $this->userRepository->findOneBy(['passwordResetToken' => $request->token]);

        if ($user === null || !$user->isPasswordResetTokenValid()) {
            return false;
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $request->password);
        $user->setPassword($hashedPassword);
        $user->clearPasswordResetToken();

        // Revoke all refresh tokens for security
        $this->tokenService->revokeAllUserTokens($user);

        $this->userRepository->save($user, true);

        return true;
    }

    public function getUserFromToken(string $accessToken): ?User
    {
        $payload = $this->tokenService->validateAccessToken($accessToken);

        if ($payload === null || !isset($payload['sub'])) {
            return null;
        }

        return $this->userRepository->find($payload['sub']);
    }

    public function updateProfile(User $user, UpdateProfileRequest $request): array
    {
        // Check if email is being changed and if it's already taken
        if ($user->getEmail() !== $request->email) {
            if ($this->userRepository->existsByEmail($request->email)) {
                throw AuthException::userExists();
            }
            $user->setEmail($request->email);
        }

        $user->setName($request->name);
        $this->userRepository->save($user, true);

        return $this->createUserData($user);
    }

    public function changePassword(User $user, ChangePasswordRequest $request): bool
    {
        // Verify current password
        if (!$this->passwordHasher->isPasswordValid($user, $request->currentPassword)) {
            return false;
        }

        // Set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $request->newPassword);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user, true);

        return true;
    }

    private function createUserData(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'isEmailVerified' => $user->isEmailVerified(),
        ];
    }
}
