<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

/** Historial del asistente de preguntas (Fase 11) — tablas ai_conversations/ai_messages. */
interface AiConversationRepositoryInterface
{
    public function create(?int $userId): int;

    /** Nulo-seguro: una conversación de invitado (user_id NULL) solo "pertenece" a $userId si $userId también es null. */
    public function belongsTo(int $conversationId, ?int $userId): bool;

    public function appendMessage(int $conversationId, string $role, string $content): void;

    /**
     * Últimos $limit mensajes en orden CRONOLÓGICO (más viejo primero) —
     * lo que espera la API de cualquier proveedor de IA.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function recentMessages(int $conversationId, int $limit): array;
}
