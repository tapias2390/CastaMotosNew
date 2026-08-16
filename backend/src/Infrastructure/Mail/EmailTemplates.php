<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

/**
 * Plantillas de correo con la identidad visual de CASTAMOTO (negro/amarillo),
 * tal como pide la sección 23 del prompt maestro.
 */
final class EmailTemplates
{
    public static function verificationEmail(string $name, string $verifyUrl): array
    {
        return [
            'subject' => 'Verifica tu correo — CASTAMOTO',
            'html' => self::layout(
                'Confirma tu correo',
                "<p>Hola {$name},</p>
                <p>Gracias por registrarte en CASTAMOTO. Confirma tu correo para activar todas las funciones de tu cuenta.</p>",
                $verifyUrl,
                'Verificar mi correo'
            ),
        ];
    }

    public static function passwordResetEmail(string $name, string $resetUrl): array
    {
        return [
            'subject' => 'Recupera tu contraseña — CASTAMOTO',
            'html' => self::layout(
                'Restablece tu contraseña',
                "<p>Hola {$name},</p>
                <p>Recibimos una solicitud para restablecer tu contraseña. Si no fuiste tú, ignora este correo.</p>",
                $resetUrl,
                'Restablecer contraseña'
            ),
        ];
    }

    private static function layout(string $title, string $bodyHtml, string $ctaUrl, string $ctaLabel): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <body style="margin:0;padding:0;background:#0d0d0d;font-family:Arial,sans-serif;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0d0d0d;padding:32px 0;">
                <tr>
                    <td align="center">
                        <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:8px;overflow:hidden;">
                            <tr>
                                <td style="background:#0d0d0d;padding:24px;text-align:center;border-bottom:2px solid #f4c430;">
                                    <span style="color:#f4c430;font-size:22px;font-weight:bold;letter-spacing:1px;">CASTAMOTO</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:24px;color:#e6e6e6;font-size:15px;line-height:1.6;">
                                    <h2 style="color:#f4c430;margin-top:0;">{$title}</h2>
                                    {$bodyHtml}
                                    <p style="text-align:center;margin:28px 0;">
                                        <a href="{$ctaUrl}" style="background:#f4c430;color:#0d0d0d;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">{$ctaLabel}</a>
                                    </p>
                                    <p style="color:#8a8a8a;font-size:12px;">Si el botón no funciona, copia y pega este enlace: <br>{$ctaUrl}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }
}
