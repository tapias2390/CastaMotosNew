<?php

declare(strict_types=1);

use App\Infrastructure\Database\Seeder;

/**
 * Métodos de pago iniciales (sección 21 del prompt maestro):
 * efectivo y transferencia habilitados por defecto, tarjeta y pasarelas
 * deshabilitadas hasta que se configuren desde administración (Fase 7).
 */
return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $methods = [
            ['cash', 'Efectivo', 'Pago en efectivo según reglas del negocio.', 1, 10],
            ['bank_transfer', 'Transferencia bancaria', 'Transferencia con validación manual del comprobante.', 1, 20],
            ['card', 'Tarjeta', 'Pago con tarjeta mediante pasarela segura.', 0, 30],
            ['wompi', 'Wompi', 'Pasarela de pago Wompi.', 0, 40],
            ['mercadopago', 'Mercado Pago', 'Pasarela de pago Mercado Pago.', 0, 50],
            ['payu', 'PayU', 'Pasarela de pago PayU.', 0, 60],
            ['stripe', 'Stripe', 'Pasarela de pago Stripe.', 0, 70],
        ];

        $stmt = $connection->prepare(
            'INSERT INTO payment_methods (code, name, description, is_enabled, sort_order)
             VALUES (:code, :name, :description, :is_enabled, :sort_order)
             ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)'
        );

        foreach ($methods as [$code, $name, $description, $enabled, $order]) {
            $stmt->execute([
                'code' => $code,
                'name' => $name,
                'description' => $description,
                'is_enabled' => $enabled,
                'sort_order' => $order,
            ]);
        }
    }
};
