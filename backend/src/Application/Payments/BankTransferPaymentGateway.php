<?php

declare(strict_types=1);

namespace App\Application\Payments;

/**
 * Transferencia bancaria (sección 20): "mostrar información de transferencia
 * cuando esté habilitada". Los datos de la cuenta se leen de
 * payment_methods.config (editable desde el panel admin) — nunca
 * hardcodeados en el código (sección 21).
 */
final class BankTransferPaymentGateway implements PaymentGatewayInterface
{
    public function code(): string
    {
        return 'bank_transfer';
    }

    public function assertConfigured(array $config): void
    {
        // No es obligatorio cargar los datos de la cuenta para habilitarla
        // (el pedido igual queda "pendiente de verificación" y se coordina
        // manualmente), pero initiate() avisa con un mensaje genérico si faltan.
    }

    public function initiate(array $order, float $amount, array $config): array
    {
        $hasAccountInfo = !empty($config['account_number']);

        $message = $hasAccountInfo
            ? sprintf(
                'Transfiere a %s, cuenta %s N.° %s a nombre de %s. Sube el comprobante para que se verifique el pago.',
                $config['bank_name'] ?? 'el banco configurado',
                $config['account_type'] ?? '',
                $config['account_number'],
                $config['account_holder'] ?? 'CASTAMOTO'
            )
            : 'Transferencia bancaria: contacta a CASTAMOTO para recibir los datos de la cuenta.';

        return [
            'status' => 'pending',
            'redirect_url' => null,
            'reference' => null,
            'message' => $message,
        ];
    }
}
