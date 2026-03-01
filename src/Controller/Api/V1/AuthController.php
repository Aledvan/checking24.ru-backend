<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\DTO\Auth\LoginRequest;
use App\DTO\Auth\RegisterRequest;
use App\Service\AuthService;
use App\Service\RequestService;
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
    ) {
    }

    /**
     * Registers a new user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/register', name: 'api_v1_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(RegisterRequest::class, $data);
        $response = $this->authService->register($dto);

        return $this->json(['success' => true, 'data' => $response->toArray()], Response::HTTP_CREATED);
    }

    /**
     * Authenticates a user.
     *
     * @param Request $request HTTP request
     *
     * @return JsonResponse
     */
    #[Route('/login', name: 'api_v1_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $this->requestService->getJsonData($request);
        $dto = $this->requestService->createValidatedDto(LoginRequest::class, $data);
        $response = $this->authService->login($dto);

        return $this->json(['success' => true, 'data' => $response->toArray()]);
    }
}
