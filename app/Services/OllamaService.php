<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaService
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null, private readonly int $timeout = 120)
    {
        $this->baseUrl = $baseUrl ?? env('OLLAMA_HOST', 'http://localhost:11434');
    }

    public function generate(string $model, string $prompt): string
    {
        return $this->post($model, $prompt, []);
    }

    public function generateWithImage(string $model, string $prompt, string $imagePath): string
    {
        return $this->post($model, $prompt, [
            'images' => [base64_encode(file_get_contents($imagePath))],
        ]);
    }

    private function post(string $model, string $prompt, array $extra): string
    {
        $response = Http::timeout($this->timeout)
            ->post($this->baseUrl . '/api/generate', array_merge([
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
            ], $extra));

        if ($response->failed()) {
            throw new RuntimeException(
                sprintf('Ollama API error [%d]: %s', $response->status(), $response->body())
            );
        }

        $text = $response->json('response');

        if ($text === null) {
            throw new RuntimeException('Ollama response missing "response" key');
        }

        return $text;
    }
}
