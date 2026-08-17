<?php

declare(strict_types=1);

namespace App\Application\Payments;

use App\Exceptions\ValidationException;

/**
 * Puerto de pago (sección 20/52 del prompt maestro): "la implementación debe
 * estar desacoplada mediante una interfaz de pago". El checkout y el resto
 * de la aplicación nunca hablan con Wompi/Stripe/PayU/MercadoPago
 * directamente — siempre a través de esta interfaz, resuelta por código
 * (PaymentGatewayFactory) a partir de payment_methods.code.
 */
interface PaymentGatewayInterface
{
    /** Código único (coincide con payment_methods.code: "cash", "wompi", ...). */
    public function code(): string;

    /**
     * Se llama ANTES de confirmar el pedido (dentro de CheckoutUseCase),
     * para no crear un pedido con un método que en realidad no puede
     * procesar el pago todavía — ej. una pasarela seleccionada sin sus
     * llaves configuradas en el panel admin.
     *
     * @param array $config Config guardada en payment_methods.config (nunca hardcodeada).
     * @throws ValidationException si el método no está listo para usarse.
     */
    public function assertConfigured(array $config): void;

    /**
     * Inicia el cobro para un pedido ya creado. Para efectivo/transferencia
     * no mueve dinero de verdad — el pago queda "pending" hasta que alguien
     * lo confirme manualmente (sección 20). Una pasarela real devolvería acá
     * una URL de redirección o un intento de cobro con tarjeta tokenizada
     * (NUNCA número de tarjeta/CVV en crudo, sección 20).
     *
     * @return array{status: string, redirect_url: ?string, reference: ?string, message: ?string}
     */
    public function initiate(array $order, float $amount, array $config): array;
}
