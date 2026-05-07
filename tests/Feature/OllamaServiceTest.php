<?php

use App\Services\OllamaService;
use Illuminate\Support\Facades\Http;

it('generate returns the model response string on success', function (): void {
    Http::fake([
        '*/api/generate' => Http::response(['response' => 'Hello from Llama'], 200),
    ]);

    $result = (new OllamaService())->generate('llama3.2:1b', 'Say hello');

    expect($result)->toBe('Hello from Llama');
});

it('generate throws a descriptive exception on HTTP error', function (): void {
    Http::fake([
        '*/api/generate' => Http::response('Service Unavailable', 503),
    ]);

    expect(fn(): string => (new OllamaService())->generate('llama3.2:1b', 'Say hello'))
        ->toThrow(RuntimeException::class, '[503]');
});

it('generate throws when response key is absent from the JSON body', function (): void {
    Http::fake([
        '*/api/generate' => Http::response(['model' => 'llama3.2:1b', 'done' => true], 200),
    ]);

    expect(fn(): string => (new OllamaService())->generate('llama3.2:1b', 'Say hello'))
        ->toThrow(RuntimeException::class, 'missing "response" key');
});

it('generateWithImage returns the model response string on success', function (): void {
    Http::fake([
        '*/api/generate' => Http::response(['response' => 'Brown hair'], 200),
    ]);

    $imageFile = tempnam(sys_get_temp_dir(), 'ollama-test-') . '.jpg';
    file_put_contents($imageFile, 'fake-image-bytes');

    $result = (new OllamaService())->generateWithImage('llava:7b', 'Describe hair color', $imageFile);

    unlink($imageFile);

    expect($result)->toBe('Brown hair');
});

it('generateWithImage sends the image as base64 in the request body', function (): void {
    Http::fake([
        '*/api/generate' => Http::response(['response' => 'OK'], 200),
    ]);

    $imageFile = tempnam(sys_get_temp_dir(), 'ollama-test-') . '.jpg';
    file_put_contents($imageFile, 'fake-image-bytes');

    (new OllamaService())->generateWithImage('llava:7b', 'Describe hair color', $imageFile);

    unlink($imageFile);

    Http::assertSent(function ($request): bool {
        $images = $request->data()['images'] ?? [];
        return $images === [base64_encode('fake-image-bytes')];
    });
});
