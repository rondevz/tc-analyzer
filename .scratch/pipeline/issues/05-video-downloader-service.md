---
Status: needs-triage
---

# 05 — VideoDownloaderService

Download a TikTok video to a local path using yt-dlp as a subprocess.

## Tasks

- `app/Services/VideoDownloaderService.php`
- Shell out: `yt-dlp <url> -o <output_path> --no-playlist -q`
- Output path: `storage/videos/<handle>/<tiktok_id>.mp4`
- Create the directory if it doesn't exist
- Capture stderr; throw descriptive exception if exit code != 0
- Return the absolute path to the downloaded file

## Acceptance criteria

- Returns a path to an existing `.mp4` file on success
- Throws with stderr content on yt-dlp failure
- Output directory is created automatically

## TDD notes

Write a unit test that mocks the subprocess call (use `proc_open` or wrap in a method that can be overridden). Test happy path and failure path. Do not invoke real yt-dlp in unit tests.
