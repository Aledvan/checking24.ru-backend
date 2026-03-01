<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\AuthException;
use App\Exception\ValidationException;
use App\Service\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function __construct(
        private readonly Translator $translator,
    ) {
    }

    /**
     * Handles exception and creates JSON response.
     *
     * @param ExceptionEvent $event Kernel exception event
     *
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $response = $this->createJsonResponse($exception);
        $event->setResponse($response);
    }

    /**
     * Creates JSON response based on exception type.
     *
     * @param \Throwable $exception The thrown exception
     *
     * @return JsonResponse
     */
    private function createJsonResponse(\Throwable $exception): JsonResponse
    {
        return match (true) {
            $exception instanceof ValidationException => new JsonResponse([
                'success' => false,
                'errors' => $this->translateErrors($exception->getErrors()),
            ], Response::HTTP_BAD_REQUEST),

            $exception instanceof AuthException => new JsonResponse([
                'success' => false,
                'error' => $this->translator->trans($exception->getMessage()),
            ], $this->getAuthExceptionStatusCode($exception)),

            $exception instanceof HttpExceptionInterface => new JsonResponse([
                'success' => false,
                'error' => $exception->getMessage(),
            ], $exception->getStatusCode()),

            default => new JsonResponse([
                'success' => false,
                'error' => $this->translator->trans('error.internal_server'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR),
        };
    }

    /**
     * Translates validation error messages.
     *
     * @param array<string, array{message: string, parameters: array<string, mixed>}> $errors
     *
     * @return array<string, string>
     */
    private function translateErrors(array $errors): array
    {
        $translated = [];

        foreach ($errors as $field => $error) {
            $translated[$field] = $this->translator->trans($error['message'], $error['parameters']);
        }

        return $translated;
    }

    /**
     * Determines HTTP status code for auth exception.
     *
     * @param AuthException $exception Auth exception instance
     *
     * @return int
     */
    private function getAuthExceptionStatusCode(AuthException $exception): int
    {
        return match ($exception->getMessage()) {
            AuthException::USER_EXISTS => Response::HTTP_CONFLICT,
            AuthException::INVALID_CREDENTIALS => Response::HTTP_UNAUTHORIZED,
            default => Response::HTTP_BAD_REQUEST,
        };
    }
}
