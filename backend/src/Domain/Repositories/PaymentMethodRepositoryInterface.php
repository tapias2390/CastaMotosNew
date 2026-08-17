<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

/**
 * Métodos de pago configurables desde administración (sección 21: "MUY
 * IMPORTANTE, no quiero que los métodos de pago estén escritos directamente
 * en el código"). El checkout (Fase 5) solo necesitaba listEnabled(); la
 * Fase 7 agrega la administración completa: ver todos (incluye
 * deshabilitados) y activar/desactivar + configurar cada uno.
 */
interface PaymentMethodRepositoryInterface
{
    public function find(int $id): ?array;

    public function listEnabled(): array;

    /** Para el panel admin: todos los métodos, habilitados o no. */
    public function listAll(): array;

    /**
     * Activa/desactiva y guarda la configuración (ej. llaves públicas de una
     * pasarela) de un método existente — nunca crea ni elimina códigos, esos
     * son fijos (sección 21: la lista de proveedores posibles es parte del
     * diseño, lo que cambia en producción es cuáles están activos).
     */
    public function updateConfig(int $id, bool $isEnabled, ?array $config): void;
}
