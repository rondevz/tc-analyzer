<?php

use App\Services\HairColorDetectorService;
use App\Services\OllamaService;

it('returns unknown immediately when frame path is null without calling Ollama', function (): void {
    $ollama = new class extends OllamaService {
        public bool $called = false;

        public function generateWithImage(string $model, string $prompt, string $imagePath): string
        {
            $this->called = true;
            return '';
        }
    };

    $result = (new HairColorDetectorService($ollama))->detect(null);

    expect($result)->toBe('unknown')
        ->and($ollama->called)->toBeFalse();
});

it('returns unknown when the frame file does not exist without calling Ollama', function (): void {
    $ollama = new class extends OllamaService {
        public bool $called = false;

        public function generateWithImage(string $model, string $prompt, string $imagePath): string
        {
            $this->called = true;
            return '';
        }
    };

    $result = (new HairColorDetectorService($ollama))->detect('/tmp/nonexistent-frame.jpg');

    expect($result)->toBe('unknown')
        ->and($ollama->called)->toBeFalse();
});

it('returns trimmed hair color description from Moondream for a valid frame', function (): void {
    $frame = tempnam(sys_get_temp_dir(), 'hair-test-') . '.jpg';
    file_put_contents($frame, 'fake-image');

    $ollama = new class extends OllamaService {
        public function generateWithImage(string $model, string $prompt, string $imagePath): string
        {
            return '  dark brown  ';
        }
    };

    $result = (new HairColorDetectorService($ollama))->detect($frame);

    unlink($frame);

    expect($result)->toBe('dark brown');
});

it('passes through unknown when Moondream finds no visible hair', function (): void {
    $frame = tempnam(sys_get_temp_dir(), 'hair-test-') . '.jpg';
    file_put_contents($frame, 'fake-image');

    $ollama = new class extends OllamaService {
        public function generateWithImage(string $model, string $prompt, string $imagePath): string
        {
            return 'unknown';
        }
    };

    $result = (new HairColorDetectorService($ollama))->detect($frame);

    unlink($frame);

    expect($result)->toBe('unknown');
});
