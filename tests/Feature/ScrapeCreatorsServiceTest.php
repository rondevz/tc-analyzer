<?php

use App\Services\ScrapeCreatorsService;
use Illuminate\Support\Facades\Http;

function makeVideoAweme(string $id, int $durationMs): array
{
    return [
        'aweme_id' => $id,
        'video' => ['duration' => $durationMs],
    ];
}

it('returns up to 3 shaped videos for a valid handle', function (): void {
    Http::fake([
        '*/v3/tiktok/profile/videos*' => Http::response([
            'aweme_list' => [
                makeVideoAweme('111', 89_000),
                makeVideoAweme('222', 45_500),
                makeVideoAweme('333', 60_000),
                makeVideoAweme('444', 30_000),
            ],
        ], 200),
    ]);

    $videos = (new ScrapeCreatorsService('fake-key'))->fetchRecentVideos('@creator');

    expect($videos)->toHaveCount(3)
        ->and($videos[0])->toBe([
            'tiktok_id' => '111',
            'tiktok_url' => 'https://www.tiktok.com/@creator/video/111',
            'duration' => 89,
        ])
        ->and($videos[1]['tiktok_id'])->toBe('222')
        ->and($videos[2]['tiktok_id'])->toBe('333');
});

it('throws a descriptive exception on a 4xx response', function (): void {
    Http::fake([
        '*/v3/tiktok/profile/videos*' => Http::response(['error' => 'handle not found'], 404),
    ]);

    expect(fn(): array => (new ScrapeCreatorsService('fake-key'))->fetchRecentVideos('@nonexistent'))
        ->toThrow(RuntimeException::class, '[404]');
});

it('throws when the video list is empty', function (): void {
    Http::fake([
        '*/v3/tiktok/profile/videos*' => Http::response(['aweme_list' => []], 200),
    ]);

    expect(fn(): array => (new ScrapeCreatorsService('fake-key'))->fetchRecentVideos('@empty'))
        ->toThrow(RuntimeException::class, 'No videos');
});

it('never sends download_media in the request', function (): void {
    Http::fake([
        '*/v3/tiktok/profile/videos*' => Http::response([
            'aweme_list' => [makeVideoAweme('111', 10_000)],
        ], 200),
    ]);

    (new ScrapeCreatorsService('fake-key'))->fetchRecentVideos('@creator');

    Http::assertSent(fn($request): bool => ! array_key_exists('download_media', $request->data()));
});
