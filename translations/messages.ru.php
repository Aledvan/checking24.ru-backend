<?php

declare(strict_types=1);

return [
    // Auth errors
    'auth.user_exists' => 'Пользователь с таким email уже существует',
    'auth.invalid_credentials' => 'Неверный email или пароль',

    // Validation errors
    'validation.error' => 'Ошибка валидации',
    'validation.email_required' => 'Email обязателен',
    'validation.email_invalid' => 'Некорректный формат email',
    'validation.password_required' => 'Пароль обязателен',
    'validation.password_min_length' => 'Пароль должен содержать минимум {limit} символов',
    'validation.name_max_length' => 'Имя не должно превышать {limit} символов',

    // Generic errors
    'error.internal_server' => 'Внутренняя ошибка сервера',
];
