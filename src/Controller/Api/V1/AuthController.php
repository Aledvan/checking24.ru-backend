<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\DTO\Auth\ForgotPasswordRequest;
use App\DTO\Auth\LoginRequest;
use App\DTO\Auth\RegisterRequest;
use App\DTO\Auth\ResendVerificationRequest;
use App\DTO\Auth\ResetPasswordRequest;
use App\DTO\Auth\VerifyEmailRequest;
use App\Service\AuthService;
use App\Service\RequestService;
use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RequestService $requestService,
        private readonly TokenService $tokenService,
    ) {
    }

    #[Route('/register', name: 'api_v1_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(RegisterRequest::class, $data);

        $result = $this->authService->register(
            $dto,
            $request->headers->get('User-Agent'),
            $request->getClientIp()
        );

        $response = $this->json([
            'success' => true,
            'data' => [
                'user' => $result['user'],
                'accessToken' => $result['tokens']['accessToken'],
                'expiresIn' => $result['tokens']['expiresIn'],
            ],
        ], Response::HTTP_CREATED);

        // Set refresh token in httpOnly cookie
        $response->headers->setCookie(
            $this->tokenService->createRefreshTokenCookie($result['tokens']['refreshToken'])
        );

        return $response;
    }

    #[Route('/login', name: 'api_v1_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(LoginRequest::class, $data);

        $result = $this->authService->login(
            $dto,
            $request->headers->get('User-Agent'),
            $request->getClientIp()
        );

        $response = $this->json([
            'success' => true,
            'data' => [
                'user' => $result['user'],
                'accessToken' => $result['tokens']['accessToken'],
                'expiresIn' => $result['tokens']['expiresIn'],
            ],
        ]);

        // Set refresh token in httpOnly cookie
        $response->headers->setCookie(
            $this->tokenService->createRefreshTokenCookie($result['tokens']['refreshToken'])
        );

        return $response;
    }

    #[Route('/auth/logout', name: 'api_v1_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get('refresh_token');

        if ($refreshToken) {
            $this->authService->logout($refreshToken);
        }

        $response = $this->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);

        // Clear refresh token cookie
        $response->headers->setCookie($this->tokenService->createLogoutCookie());

        return $response;
    }

    #[Route('/auth/refresh', name: 'api_v1_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get('refresh_token');

        if (!$refreshToken) {
            return $this->json([
                'success' => false,
                'error' => 'Refresh token not found',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tokens = $this->authService->refresh(
            $refreshToken,
            $request->headers->get('User-Agent'),
            $request->getClientIp()
        );

        if ($tokens === null) {
            $response = $this->json([
                'success' => false,
                'error' => 'Invalid refresh token',
            ], Response::HTTP_UNAUTHORIZED);

            // Clear invalid cookie
            $response->headers->setCookie($this->tokenService->createLogoutCookie());

            return $response;
        }

        $response = $this->json([
            'success' => true,
            'data' => [
                'accessToken' => $tokens['accessToken'],
                'expiresIn' => $tokens['expiresIn'],
            ],
        ]);

        // Set new refresh token in httpOnly cookie
        $response->headers->setCookie(
            $this->tokenService->createRefreshTokenCookie($tokens['refreshToken'])
        );

        return $response;
    }

    #[Route('/auth/me', name: 'api_v1_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($authHeader, 7);
        $user = $this->authService->getUserFromToken($token);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
                'isEmailVerified' => $user->isEmailVerified(),
            ],
        ]);
    }

    #[Route('/auth/verify-email', name: 'api_v1_verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(VerifyEmailRequest::class, $data);

        $success = $this->authService->verifyEmail($dto->token);

        if (!$success) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid or expired verification token',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success' => true,
            'message' => 'Email verified successfully',
        ]);
    }

    #[Route('/auth/resend-verification', name: 'api_v1_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(ResendVerificationRequest::class, $data);

        $this->authService->resendVerificationEmail($dto->email);

        // Always return success to prevent email enumeration
        return $this->json([
            'success' => true,
            'message' => 'If the email exists and is not verified, a new verification email has been sent',
        ]);
    }

    #[Route('/auth/forgot-password', name: 'api_v1_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(ForgotPasswordRequest::class, $data);

        $this->authService->requestPasswordReset($dto->email);

        // Always return success to prevent email enumeration
        return $this->json([
            'success' => true,
            'message' => 'If the email exists, a password reset link has been sent',
        ]);
    }

    #[Route('/auth/reset-password', name: 'api_v1_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(ResetPasswordRequest::class, $data);

        $success = $this->authService->resetPassword($dto);

        if (!$success) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid or expired reset token',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success' => true,
            'message' => 'Password reset successfully',
        ]);
    }
}
