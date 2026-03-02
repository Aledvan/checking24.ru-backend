<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\DTO\Site\CreateSiteRequest;
use App\DTO\Site\UpdateSiteRequest;
use App\Service\AuthService;
use App\Service\RequestService;
use App\Service\SiteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/monitoring')]
class MonitoringController extends AbstractController
{
    public function __construct(
        private readonly SiteService $siteService,
        private readonly AuthService $authService,
        private readonly RequestService $requestService,
    ) {
    }

    /**
     * Gets all sites for authenticated user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('', name: 'api_v1_monitoring_list', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $sites = $this->siteService->getSitesForUser($user);

        return $this->json([
            'success' => true,
            'data' => $sites,
        ]);
    }

    /**
     * Gets a single site by ID.
     *
     * @param Request $request HTTP request
     * @param int $id Site ID
     *
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_v1_monitoring_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $site = $this->siteService->getSiteForUser($id, $user);

        if ($site === null) {
            return $this->json([
                'success' => false,
                'error' => 'Site not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $site,
        ]);
    }

    /**
     * Creates a new site.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/add', name: 'api_v1_monitoring_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(CreateSiteRequest::class, $data);

        $site = $this->siteService->createSite($dto, $user);

        return $this->json([
            'success' => true,
            'data' => $site,
        ], Response::HTTP_CREATED);
    }

    /**
     * Updates an existing site.
     *
     * @param Request $request HTTP request
     * @param int $id Site ID
     *
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_v1_monitoring_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(UpdateSiteRequest::class, $data);

        $site = $this->siteService->updateSite($id, $dto, $user);

        if ($site === null) {
            return $this->json([
                'success' => false,
                'error' => 'Site not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data' => $site,
        ]);
    }

    /**
     * Deletes a site.
     *
     * @param Request $request HTTP request
     * @param int $id Site ID
     *
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_v1_monitoring_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        if ($user === null) {
            return $this->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $deleted = $this->siteService->deleteSite($id, $user);

        if (!$deleted) {
            return $this->json([
                'success' => false,
                'error' => 'Site not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'message' => 'Site deleted successfully',
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
