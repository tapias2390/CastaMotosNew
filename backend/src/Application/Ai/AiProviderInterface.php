<?php

declare(strict_types=1);

namespace App\Application\Ai;

/**
 * Fase 11 (asistente de preguntas): una interfaz por proveedor real, mismo
 * patrón que PaymentGatewayInterface — agregar un proveedor nuevo es
 * escribir su clase e incluirla en AiProviderFactory, nunca tocar el
 * caso de uso que hace las preguntas.
 */
interface AiProviderInterface
{
    /**
     * @param array<int, array{role: string, content: string}> $messages Historial
     *   de la conversación en orden cronológico (roles "user"/"assistant").
     * @throws \RuntimeException si la llamada al proveedor falla.
     */
    public function reply(string $systemPrompt, array $messages): string;
}
