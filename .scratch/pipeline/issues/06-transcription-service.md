---
Status: done
---

# 06 — TranscriptionService

Extract audio from a video and transcribe it to text using local Whisper (small model).

## Tasks

- `app/Services/TranscriptionService.php`
- Step 1: extract audio via ffmpeg: `ffmpeg -i <video.mp4> -vn -acodec pcm_s16le -ar 16000 -ac 1 <video.wav> -y`
- Step 2: transcribe via whisper subprocess: `whisper <video.wav> --model small --output_format txt --output_dir <tmpdir>`
- Read the resulting `.txt` file and return its contents as a string
- Delete the `.wav` file after transcription (regardless of `--keep-videos` — wav is always purged)
- Return empty string if whisper produces no output (silent video)
- Throw on ffmpeg or whisper non-zero exit

## Acceptance criteria

- Returns transcript string (may be empty) on success
- `.wav` is always deleted after transcription
- Throws descriptive exception on subprocess failure

## TDD notes

Unit test with mocked subprocess calls. Test: normal transcript, empty transcript, ffmpeg failure, whisper failure.
