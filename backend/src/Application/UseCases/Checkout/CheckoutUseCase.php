<?php

declare(strict_types=1);

namespace App\Application\UseCases\Checkout;

use App\Application\Payments\PaymentGatewayFactory;
use App\Application\Support\CartPricingCalculator;
use App\Application\Support\OrderNumberGenerator;
use App\Domain\Repositories\AddressRepositoryInterface;
use App\Domain\Repositories\CartRepositoryInterface;
use App\Domain\Repositories\OrderRepositoryInterface;
use App\Domain\Repositories\PaymentMethodRepositoryInterface;
use App\Exceptions\ValidationException;
use App\Infrastructure\Config\Config;

/**
 * Pasos 2-6 del checkout (sección 19): valida dirección/método de pago,
 * recalcula todo el carrito con datos EN VIVO (sección 54) y delega en
 * OrderRepository::createFromCheckout() la transacción atómica que crea
 * el pedido y descuenta stock (sección 35).
 */
final class CheckoutUseCase
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private OrderRepositoryInterface $orders,
        private AddressRepositoryInterface $addresses,
        private PaymentMethodRepositoryInterface $paymentMethods
    ) {
    }

    public function handle(
        int $userId,
        int $cartId,
        int $addressId,
        int $paymentMethodId,
        string $deliveryMethod,
        ?string $notes
    ): array {
        if (!$this->addresses->belongsToUser($addressId, $userId)) {
            throw new ValidationException('No fue posible confirmar el pedido.', [
                'address_id' => ['La dirección seleccionada no es válida.'],
            ]);
        }

        $paymentMethod = $this->paymentMethods->find($paymentMethodId);
        if ($paymentMethod === null || (int) $paymentMethod['is_enabled'] !== 1) {
            throw new ValidationException('No fue posible confirmar el pedido.', [
                'payment_method_id' => ['El método de pago seleccionado no está disponible.'],
            ]);
        }

        // Sección 52: el método está "activado" en la lista, pero eso no
        // garantiza que ya pueda procesar un cobro de verdad (ej. una
        // pasarela activada sin sus llaves cargadas todavía) — se falla acá,
        // ANTES de crear el pedido, no después de que el cliente ya pagó.
        PaymentGatewayFactory::make($paymentMethod['code'])->assertConfigured($paymentMethod['config'] ?? []);

        $items = $this->carts->itemsWithLiveData($cartId);
        $this->assertItemsAreCheckoutable($items);

        $pricing = CartPricingCalculator::calculate(
            $items,
            $deliveryMethod,
            (float) Config::get('app.shipping.flat_rate', 12000),
            (float) Config::get('app.shipping.free_threshold', 300000)
        );

        $orderNumber = OrderNumberGenerator::generate(fn (string $number) => $this->orders->existsByOrderNumber($number));

        $orderItems = array_map(static function (array $item) {
            return [
                'product_id' => $item['type'] === 'product' ? $item['reference_id'] : null,
                'service_id' => $item['type'] === 'service' ? $item['reference_id'] : null,
                'name_snapshot' => $item['name'],
                'sku_snapshot' => $item['sku'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => round($item['unit_price'] * $item['quantity'], 2),
                // Reserva del servicio (sección 12) — null en items de producto.
                'scheduled_at' => $item['scheduled_at'] ?? null,
            ];
        }, $items);

        // Nota: no se usa "...$pricing" (spread con claves string) porque requiere
        // PHP 8.1+; este proyecto se mantiene compatible con PHP 8.0 (ver README).
        $orderPayload = array_merge([
            'order_number' => $orderNumber,
            'user_id' => $userId,
            'address_id' => $addressId,
            'store_id' => null,
            'delivery_method' => $deliveryMethod,
            'payment_method_id' => $paymentMethodId,
            'notes' => $notes,
            'cart_id' => $cartId,
            'items' => $orderItems,
        ], $pricing);

        return $this->orders->createFromCheckout($orderPayload);
    }

    private function assertItemsAreCheckoutable(array $items): void
    {
        if (empty($items)) {
            throw new ValidationException('No fue posible confirmar el pedido.', [
                'cart' => ['El carrito está vacío.'],
            ]);
        }

        foreach ($items as $item) {
            if (!$item['is_available']) {
                throw new ValidationException('No fue posible confirmar el pedido.', [
                    'cart' => ["\"{$item['name']}\" ya no está disponible."],
                ]);
            }

            if ($item['quantity_exceeds_stock']) {
                throw new ValidationException('No fue posible confirmar el pedido.', [
                    'cart' => ["\"{$item['name']}\" ya no tiene stock suficiente."],
                ]);
            }

            if ($item['type'] === 'service' && empty($item['scheduled_at'])) {
                throw new ValidationException('No fue posible confirmar el pedido.', [
                    'cart' => ["\"{$item['name']}\" no tiene fecha y hora agendada."],
                ]);
            }
        }
    }
}
