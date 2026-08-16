<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

/**
 * Solo lo mínimo que necesita el checkout (Fase 5): consultar si un método
 * de pago existe y está habilitado. La administración completa (activar/
 * desactivar, configurar pasarelas) es la Fase 7.
 */
interface PaymentMethodRepositoryInterface
{
    public function find(int $id): ?array;

    public function listEnabled(): array;
}
