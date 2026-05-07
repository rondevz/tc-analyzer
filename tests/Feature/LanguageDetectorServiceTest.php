<?php

use App\Services\LanguageDetectorService;
use App\Services\OllamaService;

it('returns empty array immediately for empty transcripts without calling Ollama', function () {
    $ollama = new class extends OllamaService {
        public bool $called = false;
        public function generate(string $model, string $prompt): string
        {
            $this->called = true;
            return '';
        }
    };

    $result = (new LanguageDetectorService($ollama))->detect([]);

    expect($result)->toBe([])
        ->and($ollama->called)->toBeFalse();
});

it('returns the detected language code for an English transcript', function () {
    $ollama = new class extends OllamaService {
        public function generate(string $model, string $prompt): string { return '["en"]'; }
    };

    $result = (new LanguageDetectorService($ollama))->detect(['Hello, welcome to my channel.']);

    expect($result)->toBe(['en']);
});

it('returns multiple language codes for mixed-language transcripts', function () {
    $ollama = new class extends OllamaService {
        public function generate(string $model, string $prompt): string { return '["en", "es"]'; }
    };

    $result = (new LanguageDetectorService($ollama))->detect([
        'Hello, welcome to my channel.',
        'Hola, bienvenidos a mi canal.',
    ]);

    expect($result)->toBe(['en', 'es']);
});

it('returns empty array when the LLM response is not valid JSON', function () {
    $ollama = new class extends OllamaService {
        public function generate(string $model, string $prompt): string { return 'The languages are English and Spanish.'; }
    };

    $result = (new LanguageDetectorService($ollama))->detect(['some transcript']);

    expect($result)->toBe([]);
});

it('filters out entries that are not 2-letter strings', function () {
    $ollama = new class extends OllamaService {
        public function generate(string $model, string $prompt): string { return '["en", "english", "e", 42]'; }
    };

    $result = (new LanguageDetectorService($ollama))->detect(['some transcript']);

    expect($result)->toBe(['en']);
});
