<?php

declare(strict_types=1);

namespace App\Application\Payments;

/** Pago en efectivo (sección 20): siempre disponible, sin configuración externa. */
final class CashPaymentGateway implements PaymentGatewayInterface
{
    public function code(): string
    {
        return 'cash';
    }

    public function assertConfigured(array $config): void
    {
        // Nada que validar: el efectivo no depende de ninguna llave externa.
    }

    public function initiate(array $order, float $amount, array $config): array
    {
        return [
            'status' => 'pending',
            'redirect_url' => null,
            'reference' => null,
            'message' => 'Pago en efectivo al recibir o recoger el pedido.',
        ];
    }
}
