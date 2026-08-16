<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Domain\Repositories\PasswordResetRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Mail\EmailTemplates;
use App\Infrastructure\Mail\Mailer;

final class RequestPasswordResetUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordResetRepositoryInterface $passwordResets
    ) {
    }

    public function handle(string $email): void
    {
        $user = $this->users->findByEmail($email);

        // Se responde igual exista o no la cuenta (evita enumeración de usuarios, sección 6).
        if ($user === null) {
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        $ttlMinutes = (int) Config::get('app.auth.password_reset_ttl_minutes', 60);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlMinutes * 60);

        $this->passwordResets->create($email, hash('sha256', $rawToken), $expiresAt);

        $resetUrl = rtrim((string) Config::get('app.url'), '/') . '/api/auth/reset-password?token=' . $rawToken;
        $content = EmailTemplates::passwordResetEmail($user->name, $resetUrl);

        Mailer::send($email, $content['subject'], $content['html'], 'password_reset', $user->id);
    }
}
