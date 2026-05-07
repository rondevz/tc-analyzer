<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ScrapeCreatorsService
{
    private const BASE_URL = 'https://api.scrapecreators.com';

    public function __construct(private readonly string $apiKey) {}

    /**
     * @return array{tiktok_id: string, tiktok_url: string, duration: int}[]
     *
     * @throws RuntimeException on non-2xx response or empty video list
     */
    public function fetchRecentVideos(string $handle): array
    {
        $response = Http::withHeaders(['x-api-key' => $this->apiKey])
            ->get(self::BASE_URL . '/v3/tiktok/profile/videos', ['handle' => $handle]);

        if ($response->failed()) {
            throw new RuntimeException(
                "ScrapeCreators API error for {$handle} [{$response->status()}]: {$response->body()}"
            );
        }

        $awemeList = $response->json('aweme_list') ?? [];

        if (empty($awemeList)) {
            throw new RuntimeException("No videos returned for handle {$handle}");
        }

        return array_map(
            fn(array $aweme) => [
                'tiktok_id' => (string) $aweme['aweme_id'],
                'tiktok_url' => "https://www.tiktok.com/{$handle}/video/{$aweme['aweme_id']}",
                'duration' => (int) round(($aweme['video']['duration'] ?? 0) / 1000),
            ],
            array_slice($awemeList, 0, 3)
        );
    }
}
