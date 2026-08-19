<?php

declare(strict_types=1);

namespace App\Application\Ai;

/** Claude vía la Messages API (api.anthropic.com) — sin SDK, un solo cURL. */
final class AnthropicAiProvider implements AiProviderInterface
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
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
            'system' => $systemPrompt,
            'messages' => array_map(
                static fn (array $m) => ['role' => $m['role'], 'content' => $m['content']],
                $messages
            ),
        ];

        $response = $this->post($payload);

        return (string) ($response['content'][0]['text'] ?? '');
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
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::API_VERSION,
            ],
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException("No fue posible contactar a Anthropic: {$error}");
        }

        $decoded = json_decode((string) $body, true);

        if ($status !== 200) {
            $message = $decoded['error']['message'] ?? $body;
            throw new \RuntimeException("Anthropic respondió {$status}: {$message}");
        }

        return $decoded ?? [];
    }
}
