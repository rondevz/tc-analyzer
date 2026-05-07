<?php

namespace App\Services;

class HairColorDetectorService
{
    public function __construct(private readonly OllamaService $ollama) {}

    public function detect(?string $framePath): string
    {
        if ($framePath === null || ! file_exists($framePath)) {
            return 'unknown';
        }

        $prompt = 'Describe the hair color of the main person in this image in 2-5 words. If no person is visible or hair is not visible, respond with: unknown';

        $result = trim($this->ollama->generateWithImage('moondream', $prompt, $framePath));

        return $result !== '' ? $result : 'unknown';
    }
}
