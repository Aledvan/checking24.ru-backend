<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
        private readonly string $frontendUrl,
    ) {
    }

    public function sendEmailVerification(User $user): void
    {
        $token = $user->getEmailVerificationToken();
        $verifyUrl = "{$this->frontendUrl}/verify-email?token={$token}";

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Подтверждение email адреса')
            ->html($this->getEmailVerificationHtml($user, $verifyUrl))
            ->text($this->getEmailVerificationText($user, $verifyUrl));

        $this->mailer->send($email);
    }

    public function sendPasswordReset(User $user): void
    {
        $token = $user->getPasswordResetToken();
        $resetUrl = "{$this->frontendUrl}/reset-password?token={$token}";

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Сброс пароля')
            ->html($this->getPasswordResetHtml($user, $resetUrl))
            ->text($this->getPasswordResetText($user, $resetUrl));

        $this->mailer->send($email);
    }

    private function getEmailVerificationHtml(User $user, string $url): string
    {
        $name = $user->getName() ?? 'Пользователь';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #2563eb;">Подтверждение email адреса</h1>
                <p>Здравствуйте, {$name}!</p>
                <p>Благодарим за регистрацию. Для завершения регистрации подтвердите ваш email адрес.</p>
                <p style="margin: 30px 0;">
                    <a href="{$url}"
                       style="background-color: #2563eb; color: white; padding: 12px 24px;
                              text-decoration: none; border-radius: 6px; display: inline-block;">
                        Подтвердить email
                    </a>
                </p>
                <p>Или перейдите по ссылке:</p>
                <p><a href="{$url}">{$url}</a></p>
                <p style="color: #666; font-size: 14px;">
                    Ссылка действительна в течение 24 часов.
                </p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                <p style="color: #999; font-size: 12px;">
                    Если вы не регистрировались на нашем сайте, просто проигнорируйте это письмо.
                </p>
            </div>
        </body>
        </html>
        HTML;
    }

    private function getEmailVerificationText(User $user, string $url): string
    {
        $name = $user->getName() ?? 'Пользователь';

        return <<<TEXT
        Подтверждение email адреса

        Здравствуйте, {$name}!

        Благодарим за регистрацию. Для завершения регистрации подтвердите ваш email адрес, перейдя по ссылке:

        {$url}

        Ссылка действительна в течение 24 часов.

        Если вы не регистрировались на нашем сайте, просто проигнорируйте это письмо.
        TEXT;
    }

    private function getPasswordResetHtml(User $user, string $url): string
    {
        $name = $user->getName() ?? 'Пользователь';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #2563eb;">Сброс пароля</h1>
                <p>Здравствуйте, {$name}!</p>
                <p>Вы запросили сброс пароля для вашего аккаунта.</p>
                <p style="margin: 30px 0;">
                    <a href="{$url}"
                       style="background-color: #2563eb; color: white; padding: 12px 24px;
                              text-decoration: none; border-radius: 6px; display: inline-block;">
                        Сбросить пароль
                    </a>
                </p>
                <p>Или перейдите по ссылке:</p>
                <p><a href="{$url}">{$url}</a></p>
                <p style="color: #666; font-size: 14px;">
                    Ссылка действительна в течение 1 часа.
                </p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                <p style="color: #999; font-size: 12px;">
                    Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.
                </p>
            </div>
        </body>
        </html>
        HTML;
    }

    private function getPasswordResetText(User $user, string $url): string
    {
        $name = $user->getName() ?? 'Пользователь';

        return <<<TEXT
        Сброс пароля

        Здравствуйте, {$name}!

        Вы запросили сброс пароля для вашего аккаунта. Для сброса пароля перейдите по ссылке:

        {$url}

        Ссылка действительна в течение 1 часа.

        Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.
        TEXT;
    }
}
