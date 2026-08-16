<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE ai_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                conversation_id INT UNSIGNED NOT NULL,
                role ENUM('user', 'assistant', 'system') NOT NULL,
                content TEXT NOT NULL,
                metadata JSON NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ai_messages_conversation (conversation_id),
                CONSTRAINT fk_ai_messages_conversation FOREIGN KEY (conversation_id) REFERENCES ai_conversations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS ai_messages');
    }
};
