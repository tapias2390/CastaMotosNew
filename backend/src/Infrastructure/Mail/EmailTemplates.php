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

    /**
     * "Se crea pedido" (sección 23) — el primer correo del ciclo de vida,
     * apenas se confirma el checkout (estado inicial siempre PENDIENTE).
     */
    public static function orderCreatedEmail(string $name, string $orderNumber, float $total, string $orderUrl): array
    {
        return [
            'subject' => "Recibimos tu pedido {$orderNumber} — CASTAMOTO",
            'html' => self::layout(
                '¡Gracias por tu compra!',
                "<p>Hola {$name},</p>
                <p>Recibimos tu pedido <strong>{$orderNumber}</strong> por un total de " . self::formatCop($total) . ".</p>
                <p>Te avisaremos por correo en cada paso: cuando se confirme, cuando el pago quede confirmado, cuando esté en preparación, en camino y entregado.</p>",
                $orderUrl,
                'Ver mi pedido'
            ),
        ];
    }

    /**
     * Resto del ciclo de vida del pedido (sección 22/23): confirmado, pago
     * confirmado, preparación, en camino, entregado, cancelado. Un solo
     * método porque las seis comparten la misma forma (encabezado + mensaje
     * + total + botón "ver pedido") — solo cambia el texto según el estado.
     * Los estados que la sección 23 no menciona (PAGO_PENDIENTE, DEVUELTO)
     * no generan correo: devuelve null y el llamador simplemente no envía nada.
     */
    public static function orderStatusEmail(string $status, string $name, string $orderNumber, float $total, string $orderUrl): ?array
    {
        $content = self::STATUS_CONTENT[$status] ?? null;
        if ($content === null) {
            return null;
        }

        return [
            'subject' => "{$content['subject']} — Pedido {$orderNumber}",
            'html' => self::layout(
                $content['heading'],
                "<p>Hola {$name},</p>
                <p>{$content['message']}</p>
                <p>Pedido <strong>{$orderNumber}</strong> — Total: " . self::formatCop($total) . '.</p>',
                $orderUrl,
                'Ver mi pedido'
            ),
        ];
    }

    private const STATUS_CONTENT = [
        'CONFIRMADO' => [
            'subject' => 'Tu pedido fue confirmado',
            'heading' => 'Pedido confirmado',
            'message' => 'Confirmamos tu pedido y ya lo estamos procesando.',
        ],
        'PAGO_CONFIRMADO' => [
            'subject' => 'Confirmamos tu pago',
            'heading' => 'Pago confirmado',
            'message' => 'Ya confirmamos tu pago. En breve empezamos a preparar tu pedido.',
        ],
        'PREPARANDO' => [
            'subject' => 'Tu pedido está en preparación',
            'heading' => 'Preparando tu pedido',
            'message' => 'Estamos alistando tu pedido para el envío o para que lo recojas.',
        ],
        'EN_CAMINO' => [
            'subject' => 'Tu pedido está en camino',
            'heading' => 'Pedido en camino',
            'message' => 'Tu pedido ya salió y va en camino a tu dirección.',
        ],
        'ENTREGADO' => [
            'subject' => 'Tu pedido fue entregado',
            'heading' => 'Pedido entregado',
            'message' => '¡Tu pedido fue entregado! Gracias por comprar en CASTAMOTO.',
        ],
        'CANCELADO' => [
            'subject' => 'Tu pedido fue cancelado',
            'heading' => 'Pedido cancelado',
            'message' => 'Tu pedido fue cancelado. Si tienes dudas, contáctanos.',
        ],
    ];

    /** Formato de moneda simple (COP, sin decimales) — mismo criterio que
     * helpers.formatCurrency() en el frontend, acá del lado del correo. */
    private static function formatCop(float $amount): string
    {
        return '$' . number_format($amount, 0, ',', '.') . ' COP';
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
