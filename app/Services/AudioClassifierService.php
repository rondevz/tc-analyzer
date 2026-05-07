<?php

namespace App\Services;

class AudioClassifierService
{
    public function __construct(private readonly OllamaService $ollama) {}

    public function classify(string $transcript): string
    {
        if ($transcript === '') {
            return 'noise';
        }

        $prompt = <<<PROMPT
            The transcript below may be in any language. Classify it as exactly one of: speech, song, noise.
            Output only the single word. No explanation, no punctuation, no markdown.

            Transcript:
            {$transcript}
            PROMPT;

        $response = strtolower(trim($this->ollama->generate('llama3.2:1b', $prompt)));
        $response = trim((string) preg_replace('/^```[a-z]*\n?|\n?```$/m', '', $response));

        if (in_array($response, ['speech', 'song', 'noise'], true)) {
            return $response;
        }

        foreach (['speech', 'song', 'noise'] as $class) {
            if (preg_match('/\b' . $class . '\b/', $response)) {
                return $class;
            }
        }

        return 'noise';
    }
}
