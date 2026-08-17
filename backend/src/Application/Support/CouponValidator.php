<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Exceptions\ValidationException;

/**
 * Reglas de validez de un cupón (sección 30): activo, dentro de su vigencia,
 * con cupos disponibles, y la compra alcanza el mínimo. Función pura — no
 * toca la BD, solo evalúa la fila del cupón ya leída contra el subtotal del
 * carrito. La usan tanto el endpoint de "aplicar cupón" (feedback inmediato)
 * como CheckoutUseCase (re-verificación autoritativa, mismo criterio que el
 * stock y las pasarelas de pago — sección 54).
 */
final class CouponValidator
{
    /** @throws ValidationException si el cupón no se puede usar ahora mismo. */
    public static function assertUsable(array $coupon, float $cartSubtotal): void
    {
        if ($coupon['status'] !== 'active') {
            throw new ValidationException('No fue posible aplicar el cupón.', [
                'code' => ['Este cupón ya no está disponible.'],
            ]);
        }

        $now = time();

        if (!empty($coupon['starts_at']) && strtotime($coupon['starts_at']) > $now) {
            throw new ValidationException('No fue posible aplicar el cupón.', [
                'code' => ['Este cupón todavía no está vigente.'],
            ]);
        }

        if (!empty($coupon['ends_at']) && strtotime($coupon['ends_at']) < $now) {
            throw new ValidationException('No fue posible aplicar el cupón.', [
                'code' => ['Este cupón ya venció.'],
            ]);
        }

        if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            throw new ValidationException('No fue posible aplicar el cupón.', [
                'code' => ['Este cupón ya alcanzó su límite de usos.'],
            ]);
        }

        if ($coupon['min_purchase'] !== null && $cartSubtotal < (float) $coupon['min_purchase']) {
            throw new ValidationException('No fue posible aplicar el cupón.', [
                'code' => ['La compra mínima para este cupón es ' . number_format((float) $coupon['min_purchase'], 0, ',', '.') . '.'],
            ]);
        }
    }

    /** Monto del descuento (nunca negativo ni mayor al total disponible a descontar). */
    public static function discountAmount(array $coupon, float $amountAfterItemDiscounts): float
    {
        if ($coupon['type'] === 'percentage') {
            return round($amountAfterItemDiscounts * ((float) $coupon['value'] / 100), 2);
        }

        return round(min((float) $coupon['value'], $amountAfterItemDiscounts), 2);
    }
}
