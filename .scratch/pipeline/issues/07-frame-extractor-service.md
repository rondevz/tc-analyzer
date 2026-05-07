---
Status: done
---

# 07 — FrameExtractorService

Extract a single `.jpg` frame from a video at 30% of its duration using ffmpeg.

## Tasks

- `app/Services/FrameExtractorService.php`
- Get video duration: `ffprobe -v error -show_entries format=duration -of csv=p=0 <video.mp4>`
- Extract frame: `ffmpeg -ss <duration*0.3> -i <video.mp4> -frames:v 1 <frame.jpg> -y`
- Output path: `storage/frames/<handle>/<tiktok_id>.jpg`
- Create the directory if it doesn't exist
- Return the absolute path to the `.jpg`
- Throw on ffprobe or ffmpeg non-zero exit

## Acceptance criteria

- Returns path to an existing `.jpg` on success
- Frame is at approximately 30% of video duration
- Output directory is created automatically

## TDD notes

Unit test with mocked subprocess calls. Test happy path and failure path.
