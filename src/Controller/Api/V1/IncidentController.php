<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\AuthService;
use App\Service\IncidentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class IncidentController extends AbstractController
{
    public function __construct(
        private readonly IncidentService $incidentService,
        private readonly AuthService $authService,
    ) {
    }

    /**
     * Gets all incidents for authenticated user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/incidents', name: 'api_v1_incidents', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $incidents = $this->incidentService->getIncidentsForUser($user);

        return $this->json([
            'success' => true,
            'data' => $incidents,
        ]);
    }

    /**
     * Gets active incidents for authenticated user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/incidents/active', name: 'api_v1_incidents_active', methods: ['GET'])]
    public function active(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $incidents = $this->incidentService->getActiveIncidentsForUser($user);

        return $this->json([
            'success' => true,
            'data' => $incidents,
        ]);
    }

    /**
     * Gets resolved incidents for authenticated user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/incidents/resolved', name: 'api_v1_incidents_resolved', methods: ['GET'])]
    public function resolved(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $incidents = $this->incidentService->getResolvedIncidentsForUser($user);

        return $this->json([
            'success' => true,
            'data' => $incidents,
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
