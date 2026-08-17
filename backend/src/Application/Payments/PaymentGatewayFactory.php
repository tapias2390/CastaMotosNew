<?php

declare(strict_types=1);

namespace App\Application\Payments;

use App\Exceptions\ValidationException;

/**
 * Resuelve la implementación de PaymentGatewayInterface a partir del código
 * de payment_methods (sección 20: "PaymentGateway → Stripe / MercadoPago /
 * Wompi / PayU / Otro proveedor"). Agregar un proveedor nuevo real en el
 * futuro es: escribir su clase, agregarla acá y sembrar su fila en
 * payment_methods — nunca tocar el checkout ni el resto de la aplicación.
 */
final class PaymentGatewayFactory
{
    private const EXTERNAL_PROVIDERS = [
        'card' => 'Tarjeta',
        'wompi' => 'Wompi',
        'mercadopago' => 'Mercado Pago',
        'payu' => 'PayU',
        'stripe' => 'Stripe',
    ];

    public static function make(string $code): PaymentGatewayInterface
    {
        return match ($code) {
            'cash' => new CashPaymentGateway(),
            'bank_transfer' => new BankTransferPaymentGateway(),
            default => self::externalOrFail($code),
        };
    }

    private static function externalOrFail(string $code): PaymentGatewayInterface
    {
        if (!isset(self::EXTERNAL_PROVIDERS[$code])) {
            throw new ValidationException('No fue posible confirmar el pedido.', [
                'payment_method_id' => ['El método de pago seleccionado no está disponible.'],
            ]);
        }

        return new ExternalPaymentGateway($code, self::EXTERNAL_PROVIDERS[$code]);
    }
}
