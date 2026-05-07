---
Status: needs-triage
---

# 04 — ScrapeCreatorsService

Fetch the 3 most recent video URLs and metadata for a given handle from the ScrapeCreators API.

## Tasks

- `app/Services/ScrapeCreatorsService.php`
- Use Laravel's HTTP client (`Http::withToken(...)`)
- Call the correct ScrapeCreators endpoint for listing a creator's videos (check https://docs.scrapecreators.com — do NOT set `download_media=true`)
- Return an array of up to 3 videos: `[['tiktok_id' => ..., 'tiktok_url' => ..., 'duration' => ...], ...]`
- Throw a descriptive exception on non-2xx response or empty video list

## Acceptance criteria

- Returns correctly shaped array for a known active handle
- Throws on invalid handle / API error
- Never sets `download_media=true`

## TDD notes

Write a feature test with a mocked HTTP response (use `Http::fake()`). Test the happy path and a 4xx error path. Do not make real API calls in tests.
