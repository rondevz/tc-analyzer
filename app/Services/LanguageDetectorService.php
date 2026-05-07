<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LanguageDetectorService
{
    public function __construct(private readonly OllamaService $ollama) {}

    public function detect(array $transcripts): array
    {
        if ($transcripts === []) {
            return [];
        }

        $combined = implode("\n\n", $transcripts);

        $prompt = <<<PROMPT
            Look at the text below and identify which languages it is written in. Output ONLY a JSON array of ISO 639-1 two-letter codes based solely on the text content. No explanation, no markdown, no code fences.
            Examples: ["en"] for English only, ["es"] for Spanish only, ["en","es"] for both.

            Text:
            {$combined}
            PROMPT;

        $raw = trim($this->ollama->generate('llama3.2:1b', $prompt));
        $response = trim((string) preg_replace('/^```[a-z]*\n?|\n?```$/m', '', $raw));

        if (preg_match('/\[.*?\]/s', $response, $m)) {
            $response = $m[0];
        }

        $decoded = json_decode($response, true);

        if (! is_array($decoded)) {
            Log::warning('LanguageDetectorService: failed to parse LLM response as JSON', ['response' => $response]);
            return [];
        }

        return array_values(array_filter(
            $decoded,
            fn($code): bool => is_string($code) && strlen($code) === 2
        ));
    }
}
