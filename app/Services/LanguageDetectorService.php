<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LanguageDetectorService
{
    public function __construct(private readonly OllamaService $ollama) {}

    public function detect(array $transcripts): array
    {
        if (empty($transcripts)) {
            return [];
        }

        $combined = implode("\n\n", $transcripts);

        $prompt = <<<PROMPT
            Identify all spoken languages in the following text. Return a JSON array of ISO 639-1 codes only (e.g. ["en", "es"]). No explanation.

            Text:
            {$combined}
            PROMPT;

        $response = trim($this->ollama->generate('llama3.2:1b', $prompt));

        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            Log::warning('LanguageDetectorService: failed to parse LLM response as JSON', ['response' => $response]);
            return [];
        }

        return array_values(array_filter(
            $decoded,
            fn($code) => is_string($code) && strlen($code) === 2
        ));
    }
}
