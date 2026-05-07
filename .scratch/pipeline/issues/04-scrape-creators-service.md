---
Status: done
---

# 04 — ScrapeCreatorsService

Fetch the 3 most recent video URLs and metadata for a given handle from the ScrapeCreators API.

## Tasks

- `app/Services/ScrapeCreatorsService.php`
- Use Laravel's HTTP client with `Http::withHeaders(['x-api-key' => $apiKey])`
- Endpoint: `GET https://api.scrapecreators.com/v3/tiktok/profile/videos?handle=@handle`
- Response: `{ "aweme_list": [{ "aweme_id": "...", "video": { "duration": <ms> } }] }`
- `tiktok_url` is constructed as `https://www.tiktok.com/{handle}/video/{aweme_id}` — do NOT use `share_info.share_url` (contains tracking params)
- `duration` is `video.duration` in milliseconds, converted to integer seconds
- Do NOT send `download_media` — it is not a parameter of this endpoint
- Return an array of up to 3 videos: `[['tiktok_id' => ..., 'tiktok_url' => ..., 'duration' => ...], ...]`
- Throw a descriptive exception on non-2xx response or empty video list

## Acceptance criteria

- Returns correctly shaped array for a known active handle
- Throws on invalid handle / API error
- Never sends `download_media` in the request

## TDD notes

Write a feature test with a mocked HTTP response (use `Http::fake()`). Test the happy path, a 4xx error path, and an empty list path. Do not make real API calls in tests.
