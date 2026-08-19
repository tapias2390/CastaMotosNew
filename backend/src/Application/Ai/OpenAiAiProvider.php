<?php

declare(strict_types=1);

namespace App\Application\Ai;

/** GPT vía la Chat Completions API (api.openai.com) — sin SDK, un solo cURL. */
final class OpenAiAiProvider implements AiProviderInterface
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const MAX_TOKENS = 700;
    private const TIMEOUT_SECONDS = 20;

    public function __construct(private string $apiKey, private string $model)
    {
    }

    public function reply(string $systemPrompt, array $messages): string
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => self::MAX_TOKENS,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                array_map(static fn (array $m) => ['role' => $m['role'], 'content' => $m['content']], $messages)
            ),
        ];

        $response = $this->post($payload);

        return (string) ($response['choices'][0]['message']['content'] ?? '');
    }

    private function post(array $payload): array
    {
        $ch = curl_init(self::ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException("No fue posible contactar a OpenAI: {$error}");
        }

        $decoded = json_decode((string) $body, true);

        if ($status !== 200) {
            $message = $decoded['error']['message'] ?? $body;
            throw new \RuntimeException("OpenAI respondió {$status}: {$message}");
        }

        return $decoded ?? [];
    }
}
