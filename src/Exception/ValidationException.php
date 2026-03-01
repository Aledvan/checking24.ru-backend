<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception
{
    public const VALIDATION_ERROR = 'validation.error';

    /**
     * @var array<string, array{message: string, parameters: array<string, mixed>}>
     */
    private array $errors = [];

    /**
     * @param ConstraintViolationListInterface $violations Validation violations list
     */
    public function __construct(ConstraintViolationListInterface $violations)
    {
        foreach ($violations as $violation) {
            $parameters = [];
            foreach ($violation->getParameters() as $key => $value) {
                // Convert Symfony's {{ param }} format to {param}
                $cleanKey = trim($key, '{}% ');
                $parameters[$cleanKey] = $value;
            }

            $this->errors[$violation->getPropertyPath()] = [
                'message' => $violation->getMessageTemplate(),
                'parameters' => $parameters,
            ];
        }

        parent::__construct(self::VALIDATION_ERROR);
    }

    /**
     * Returns validation errors array with message keys and parameters.
     *
     * @return array<string, array{message: string, parameters: array<string, mixed>}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
