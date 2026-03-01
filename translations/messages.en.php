<?php

declare(strict_types=1);

return [
    // Auth errors
    'auth.user_exists' => 'User with this email already exists',
    'auth.invalid_credentials' => 'Invalid email or password',

    // Validation errors
    'validation.error' => 'Validation error',
    'validation.email_required' => 'Email is required',
    'validation.email_invalid' => 'Invalid email format',
    'validation.password_required' => 'Password is required',
    'validation.password_min_length' => 'Password must contain at least {limit} characters',
    'validation.name_max_length' => 'Name must not exceed {limit} characters',

    // Generic errors
    'error.internal_server' => 'Internal server error',
];
