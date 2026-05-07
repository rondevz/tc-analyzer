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
            Classify the following transcript as exactly one of: speech, song, noise.
            Output only the single word. No explanation.

            Transcript:
            {$transcript}
            PROMPT;

        $response = strtolower(trim($this->ollama->generate('llama3.2:1b', $prompt)));

        return in_array($response, ['speech', 'song', 'noise'], true) ? $response : 'noise';
    }
}
