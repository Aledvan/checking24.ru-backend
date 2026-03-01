<?php

declare(strict_types=1);

namespace App\Service;

class Translator
{
    private array $messages = [];
    private string $locale;

    public function __construct(string $locale = 'ru')
    {
        $this->locale = $locale;
        $this->loadMessages();
    }

    public function trans(string $key, array $parameters = []): string
    {
        $message = $this->messages[$this->locale][$key] ?? $key;

        foreach ($parameters as $param => $value) {
            $message = str_replace('{' . $param . '}', (string) $value, $message);
        }

        return $message;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        if (in_array($locale, ['ru', 'en'], true)) {
            $this->locale = $locale;
            $this->loadMessages();
        }
    }

    private function loadMessages(): void
    {
        $filePath = dirname(__DIR__, 2) . '/translations/messages.' . $this->locale . '.php';

        if (file_exists($filePath)) {
            $this->messages[$this->locale] = require $filePath;
        }
    }
}
