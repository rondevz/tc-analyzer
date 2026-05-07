<?php

use App\Models\Creator;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists and retrieves a creator', function (): void {
    $creator = Creator::create(['handle' => '@test', 'status' => 'pending']);

    expect(Creator::find('@test'))->not->toBeNull()
        ->and(Creator::find('@test')->handle)->toBe('@test')
        ->and(Creator::find('@test')->status)->toBe('pending');
});

it('round-trips spoken_languages as a PHP array', function (): void {
    Creator::create([
        'handle' => '@test',
        'status' => 'pending',
        'spoken_languages' => ['en', 'es'],
    ]);

    $languages = Creator::find('@test')->spoken_languages;

    expect($languages)->toBeArray()
        ->and($languages)->toBe(['en', 'es']);
});

it('returns videos via hasMany relation', function (): void {
    $creator = Creator::create(['handle' => '@test', 'status' => 'pending']);

    Video::create([
        'creator_handle' => '@test',
        'tiktok_id' => 'abc123',
        'tiktok_url' => 'https://tiktok.com/@test/video/abc123',
        'status' => 'pending',
    ]);

    expect($creator->videos)->toHaveCount(1)
        ->and($creator->videos->first()->tiktok_id)->toBe('abc123');
});

it('video belongs to creator', function (): void {
    Creator::create(['handle' => '@test', 'status' => 'pending']);

    $video = Video::create([
        'creator_handle' => '@test',
        'tiktok_id' => 'abc123',
        'tiktok_url' => 'https://tiktok.com/@test/video/abc123',
        'status' => 'pending',
    ]);

    expect($video->creator->handle)->toBe('@test');
});
