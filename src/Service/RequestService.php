<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestService
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Extracts data from JSON request body.
     *
     * @param Request $request HTTP request
     *
     * @return array<string, mixed>
     */
    public function getJsonData(Request $request): array
    {
        $content = $request->getContent();

        if (empty($content)) {
            return [];
        }

        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException) {
            return [];
        }
    }

    /**
     * Creates and validates DTO from request data.
     *
     * @template T of object
     *
     * @param class-string<T> $dtoClass DTO class name
     * @param array<string, mixed> $data Request data
     *
     * @return T
     *
     * @throws ValidationException
     */
    public function createValidatedDto(string $dtoClass, array $data): object
    {
        $dto = $this->createDto($dtoClass, $data);

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        return $dto;
    }

    /**
     * Creates DTO from data array.
     *
     * @template T of object
     *
     * @param class-string<T> $dtoClass DTO class name
     * @param array<string, mixed> $data Data array
     *
     * @return T
     * @throws \ReflectionException
     */
    private function createDto(string $dtoClass, array $data): object
    {
        $reflection = new \ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $dtoClass();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $args[$name] = $data[$name] ?? ($param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);
        }

        return new $dtoClass(...$args);
    }
}
