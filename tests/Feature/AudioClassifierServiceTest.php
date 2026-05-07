<?php

use App\Services\AudioClassifierService;
use App\Services\OllamaService;

it('returns noise immediately for an empty transcript without calling Ollama', function (): void {
    $ollama = new class extends OllamaService {
        public bool $called = false;

        public function generate(string $model, string $prompt): string
        {
            $this->called = true;
            return '';
        }
    };

    $result = (new AudioClassifierService($ollama))->classify('');

    expect($result)->toBe('noise')
        ->and($ollama->called)->toBeFalse();
});

it('returns speech when Ollama classifies the transcript as speech', function (): void {
    $ollama = new class extends OllamaService {
        public function generate(string $model, string $prompt): string { return 'speech'; }
    };

    $result = (new AudioClassifierService($ollama))->classify("Welcome to today's show.");

    expect($result)->toBe('speech');
});

it('returns song when Ollama classifies the transcript as song', function (): void {
    $ollama = new class extends OllamaService {
        public function generate(string $model, string $prompt): string { return 'song'; }
    };

    $result = (new AudioClassifierService($ollama))->classify('Baby baby baby oh, like baby baby baby no');

    expect($result)->toBe('song');
});

it('defaults to noise when Ollama returns an unrecognisable response', function (): void {
    $ollama = new class extends OllamaService {
        public function generate(string $model, string $prompt): string { return 'I cannot determine this.'; }
    };

    $result = (new AudioClassifierService($ollama))->classify('some transcript');

    expect($result)->toBe('noise');
});
