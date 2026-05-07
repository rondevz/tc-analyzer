---
Status: done
---

# 12 — ScanCommand

The main pipeline orchestrator. Reads handles from a CSV, runs the full pipeline for each creator, handles partial failure, and respects idempotency.

## Signature

```
scan {csv : Path to CSV file of TikTok handles}
     {--keep-videos : Retain .mp4 files after processing}
     {--limit= : Process only the first N handles}
```

## Pipeline per creator

1. `Creator::firstOrCreate(['handle' => $handle], ['status' => 'pending'])`
2. Skip if `creator.status === 'done'`
3. Mark creator `processing`
4. Fetch 3 video URLs via `ScrapeCreatorsService` → upsert into `videos` table (skip existing)
5. For each video where `status !== 'done'`:
   a. Download via `VideoDownloaderService` → mark `downloaded`
   b. Transcribe via `TranscriptionService` → save transcript → mark `transcribed`
   c. Classify audio via `AudioClassifierService` → save `audio_class` → mark `classified`
   d. If `audio_class === 'speech'` and no frame extracted yet for creator: extract frame via `FrameExtractorService` → save `frame_path`
   e. Delete `.mp4` unless `--keep-videos`
   f. Mark video `done`
6. Detect languages via `LanguageDetectorService` across all `speech` transcripts
7. Detect hair color via `HairColorDetectorService` from frame (or `'unknown'` if no frame)
8. Update creator: `spoken_languages`, `hair_color`, `status = done`, `processed_at = now()`

## Partial failure handling

- Wrap each video's processing in try/catch → on exception: mark video `failed`, save `error_message`, log warning, continue to next video
- Wrap each creator in try/catch → on exception: mark creator `failed`, log error, continue to next creator
- A creator with all videos `failed` still gets `spoken_languages: []` and `hair_color: 'unknown'`

## Acceptance criteria

- Processes all handles in CSV
- `--limit=1` processes only the first handle
- Re-running skips creators and videos already `done`
- One video failure doesn't abort the creator
- One creator failure doesn't abort the CSV

## TDD notes

Feature test: mock all services, assert correct DB state after a full run. Test the partial failure path (one video throws, creator still finishes).
