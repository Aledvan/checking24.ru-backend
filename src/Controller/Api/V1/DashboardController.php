<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\AuthService;
use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/dashboard')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly AuthService $authService,
    ) {
    }

    /**
     * Gets dashboard statistics for authenticated user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/stats', name: 'api_v1_dashboard_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $stats = $this->dashboardService->getStatistics($user);

        return $this->json([
            'success' => true,
            'data' => $stats,
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
