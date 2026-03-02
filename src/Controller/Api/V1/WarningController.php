<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\AuthService;
use App\Service\WarningService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class WarningController extends AbstractController
{
    public function __construct(
        private readonly WarningService $warningService,
        private readonly AuthService $authService,
    ) {
    }

    /**
     * Gets all active warnings for authenticated user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/warnings', name: 'api_v1_warnings', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $warnings = $this->warningService->getWarningsForUser($user);

        return $this->json([
            'success' => true,
            'data' => $warnings,
        ]);
    }

    /**
     * Gets pending warnings for authenticated user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/warnings/pending', name: 'api_v1_warnings_pending', methods: ['GET'])]
    public function pending(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $warnings = $this->warningService->getPendingWarningsForUser($user);

        return $this->json([
            'success' => true,
            'data' => $warnings,
        ]);
    }

    /**
     * Extracts authenticated user from request.
     *
     * @param Request $request HTTP request
     *
     * @return \App\Entity\User|null
     */
    private function getAuthenticatedUser(Request $request): ?\App\Entity\User
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);

        return $this->authService->getUserFromToken($token);
    }
}
