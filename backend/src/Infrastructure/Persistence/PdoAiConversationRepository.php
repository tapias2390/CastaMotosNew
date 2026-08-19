<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\AiConversationRepositoryInterface;
use PDO;

final class PdoAiConversationRepository implements AiConversationRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(?int $userId): int
    {
        $stmt = $this->connection->prepare('INSERT INTO ai_conversations (user_id) VALUES (:user_id)');
        $stmt->execute(['user_id' => $userId]);

        return (int) $this->connection->lastInsertId();
    }

    public function belongsTo(int $conversationId, ?int $userId): bool
    {
        // "<=>" (NULL-safe equal, MySQL/MariaDB) en vez de "=": una conversación
        // de invitado tiene user_id NULL, y NULL = NULL es NULL (falso) con el
        // operador normal — acá sí debe dar verdadero cuando ambos son NULL.
        $stmt = $this->connection->prepare(
            'SELECT 1 FROM ai_conversations WHERE id = :id AND user_id <=> :user_id'
        );
        $stmt->execute(['id' => $conversationId, 'user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public function appendMessage(int $conversationId, string $role, string $content): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO ai_messages (conversation_id, role, content) VALUES (:conversation_id, :role, :content)'
        );
        $stmt->execute(['conversation_id' => $conversationId, 'role' => $role, 'content' => $content]);

        // Toca updated_at de la conversación (para poder ordenar "mis conversaciones
        // recientes" el día que exista esa vista) — INSERT en ai_messages no lo hace solo.
        $this->connection->prepare('UPDATE ai_conversations SET updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $conversationId]);
    }

    public function recentMessages(int $conversationId, int $limit): array
    {
        $stmt = $this->connection->prepare(
            "SELECT role, content FROM ai_messages
             WHERE conversation_id = :conversation_id AND role != 'system'
             ORDER BY id DESC LIMIT :limit"
        );
        $stmt->bindValue('conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
