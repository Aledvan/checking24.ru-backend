<?php

declare(strict_types=1);

return [
    // Auth errors
    'auth.user_exists' => 'Пользователь с таким email уже существует',
    'auth.invalid_credentials' => 'Неверный email или пароль',
    'auth.email_not_verified' => 'Email не подтверждён',
    'auth.invalid_token' => 'Недействительный или просроченный токен',

    // Validation errors
    'validation.error' => 'Ошибка валидации',
    'validation.email_required' => 'Email обязателен',
    'validation.email_invalid' => 'Некорректный формат email',
    'validation.password_required' => 'Пароль обязателен',
    'validation.password_min_length' => 'Пароль должен содержать минимум {limit} символов',
    'validation.name_max_length' => 'Имя не должно превышать {limit} символов',
    'validation.token_required' => 'Токен обязателен',

    // Generic errors
    'error.internal_server' => 'Внутренняя ошибка сервера',
    'error.unauthorized' => 'Необходима авторизация',
];
