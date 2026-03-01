<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Auth\AuthResponse;
use App\DTO\Auth\LoginRequest;
use App\DTO\Auth\RegisterRequest;
use App\Entity\User;
use App\Exception\AuthException;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * Registers a new user.
     *
     * @param RegisterRequest $request Registration request DTO
     *
     * @return AuthResponse
     *
     * @throws AuthException
     */
    public function register(RegisterRequest $request): AuthResponse
    {
        if ($this->userRepository->existsByEmail($request->email)) {
            throw AuthException::userExists();
        }

        $user = new User();
        $user->setEmail($request->email);
        $user->setName($request->name);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $request->password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user, true);

        return $this->createAuthResponse($user);
    }

    /**
     * Authenticates a user.
     *
     * @param LoginRequest $request Login request DTO
     *
     * @return AuthResponse
     *
     * @throws AuthException
     */
    public function login(LoginRequest $request): AuthResponse
    {
        $user = $this->userRepository->findByEmail($request->email);

        if ($user === null) {
            throw AuthException::invalidCredentials();
        }

        if (!$this->passwordHasher->isPasswordValid($user, $request->password)) {
            throw AuthException::invalidCredentials();
        }

        return $this->createAuthResponse($user);
    }

    /**
     * Creates auth response DTO from user entity.
     *
     * @param User $user User entity
     *
     * @return AuthResponse
     */
    private function createAuthResponse(User $user): AuthResponse
    {
        return new AuthResponse(
            id: $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
            roles: $user->getRoles(),
        );
    }
}
