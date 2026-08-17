<?php

declare(strict_types=1);

namespace App\Application\Payments;

use App\Exceptions\ValidationException;

/**
 * Pasarela externa genérica (tarjeta, Wompi, Mercado Pago, PayU, Stripe —
 * sección 20). Una sola clase para las cinco porque ninguna tiene todavía
 * credenciales reales cargadas: en vez de simular una integración que nunca
 * se pudo probar contra el sandbox real de cada proveedor, esta clase
 * cumple la interfaz honestamente — confirma que YA HAY llaves configuradas
 * antes de dejar avanzar el checkout, y deja marcado el punto exacto donde
 * se agrega la llamada real a la API del proveedor cuando existan esas
 * credenciales (sección 51: "preparado para agregar sin reconstruir").
 *
 * NUNCA recibe ni guarda número de tarjeta/CVV (sección 20) — eso lo
 * tokeniza la pasarela del lado del cliente, este backend solo vería un
 * token de un solo uso.
 */
final class ExternalPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private string $providerCode, private string $providerLabel)
    {
    }

    public function code(): string
    {
        return $this->providerCode;
    }

    public function assertConfigured(array $config): void
    {
        if (empty($config['api_key']) && empty($config['public_key'])) {
            throw new ValidationException('No fue posible confirmar el pedido.', [
                'payment_method_id' => [
                    "El método de pago \"{$this->providerLabel}\" todavía no tiene sus credenciales configuradas en el panel admin.",
                ],
            ]);
        }
    }

    public function initiate(array $order, float $amount, array $config): array
    {
        $this->assertConfigured($config);

        // Punto de extensión: acá va la llamada real a la API de
        // {$this->providerCode} (crear intento de cobro, obtener
        // redirect_url o token de sesión de pago) una vez existan
        // credenciales reales de sandbox/producción para probarla.
        throw new ValidationException('No fue posible confirmar el pedido.', [
            'payment_method_id' => [
                "La integración con \"{$this->providerLabel}\" está preparada pero aún no conectada a su API real.",
            ],
        ]);
    }
}
