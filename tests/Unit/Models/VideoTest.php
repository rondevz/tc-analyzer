<?php

use App\Models\Creator;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Creator::create(['handle' => '@test', 'status' => 'pending']);
});

it('persists and retrieves a video', function () {
    Video::create([
        'creator_handle' => '@test',
        'tiktok_id' => 'abc123',
        'tiktok_url' => 'https://tiktok.com/@test/video/abc123',
        'status' => 'pending',
    ]);

    $video = Video::first();

    expect($video->creator_handle)->toBe('@test')
        ->and($video->tiktok_id)->toBe('abc123')
        ->and($video->status)->toBe('pending');
});

it('nullable columns default to null', function () {
    $video = Video::create([
        'creator_handle' => '@test',
        'tiktok_id' => 'abc123',
        'tiktok_url' => 'https://tiktok.com/@test/video/abc123',
        'status' => 'pending',
    ]);

    expect($video->transcript)->toBeNull()
        ->and($video->audio_class)->toBeNull()
        ->and($video->frame_path)->toBeNull()
        ->and($video->error_message)->toBeNull();
});

it('belongs to creator', function () {
    $video = Video::create([
        'creator_handle' => '@test',
        'tiktok_id' => 'abc123',
        'tiktok_url' => 'https://tiktok.com/@test/video/abc123',
        'status' => 'pending',
    ]);

    expect($video->creator->handle)->toBe('@test');
});
