<?php

declare(strict_types=1);

namespace App\Exception;

class AuthException extends \Exception
{
    public const USER_EXISTS = 'auth.user_exists';
    public const INVALID_CREDENTIALS = 'auth.invalid_credentials';

    /**
     * Creates "User exists" exception.
     *
     * @return self
     */
    public static function userExists(): self
    {
        return new self(self::USER_EXISTS);
    }

    /**
     * Creates "Invalid credentials" exception.
     *
     * @return self
     */
    public static function invalidCredentials(): self
    {
        return new self(self::INVALID_CREDENTIALS);
    }
}
