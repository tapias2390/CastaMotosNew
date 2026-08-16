<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Mail\EmailTemplates;
use App\Infrastructure\Mail\Mailer;

/**
 * Reenvía el correo de verificación. Si el correo no existe o ya está
 * verificado, no hace nada — pero el controller siempre responde el mismo
 * mensaje genérico para no revelar si una cuenta existe (sección 6).
 */
final class ResendVerificationUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function handle(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null || $user->isEmailVerified()) {
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        $ttlHours = (int) Config::get('app.auth.email_verification_ttl_hours', 24);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlHours * 3600);

        $this->users->setEmailVerificationToken($user->id, hash('sha256', $rawToken), $expiresAt);

        $verifyUrl = rtrim((string) Config::get('app.url'), '/') . '/api/auth/verify-email?token=' . $rawToken;
        $content = EmailTemplates::verificationEmail($user->name, $verifyUrl);

        Mailer::send($email, $content['subject'], $content['html'], 'email_verification', $user->id);
    }
}
