# CONTEXT.md

Domain glossary and resolved decisions for the TikTok Creator Analysis Pipeline.

## Glossary

**Creator** — a TikTok account identified by a handle (e.g. `@username`). The unit of analysis. One row in the `creators` table.

**Video** — a single TikTok video processed for a creator. Metadata (URL, duration, etc.) is fetched via ScrapeCreators API (`download_media = false`, 1 credit per call). The video file itself is downloaded via `yt-dlp` using the URL returned by ScrapeCreators — not through ScrapeCreators' paid download feature. 3 videos per creator; 10 creators = ~10 credits total for metadata. Intermediate artifact; persisted to the `videos` table with a `status` column (`pending` → `downloaded` → `transcribed` → `classified` → `done`) to allow resumable, idempotent processing. Re-running the pipeline skips videos that have already completed a step.

**Transcript** — the text output of running the local `openai/whisper` Python library (`small` model) on a video's audio track. Invoked as a subprocess from PHP. Stored per-video. May be empty (silence, music).

**Audio class** — a per-video classification of the audio content: `speech`, `song`, or `noise`. Derived from the transcript by Llama 3.2 1B. Only `speech` videos contribute to language detection.

**Spoken languages** — the set of languages a creator uses across their speech-bearing videos. Stored as JSON in `creators.spoken_languages`. Derived by Llama 3.2 1B from the concatenated transcripts of all `speech` videos.

**Representative frame** — a single `.jpg` frame extracted via `ffmpeg` at 30% of the video's duration, from the first `speech`-classified video for a creator. Used as input to Moondream for hair color detection. Kept on disk permanently under `storage/frames/<handle>/`. 

**Process & Purge** — the default file lifecycle: after a video is fully processed (transcript written, frame extracted), the `.mp4` and intermediate `.wav` files are deleted to reclaim disk. The `--keep-videos` flag on the `scan` command disables purging and retains all downloaded files.

**scan command** — the main entry point: `php application scan {csv} {--keep-videos} {--limit=}`. The `--limit` flag restricts processing to the first N handles, useful for demos. Outputs a step indicator for every pipeline stage (see below).

**Step indicator** — the per-step console output pattern used by `scan`: writes the step label and fill dots while the operation runs, then appends `✓ <result>` or `✗ <error>` on the same line once complete. Each step that produces a meaningful value surfaces it inline (transcript snippet, audio class, language codes, hair color). A per-creator summary line is printed after all steps complete.

**Hair color** — a free-text description of the creator's hair color as returned by Moondream from the representative frame. Fuzzy by design; may be `unknown` if no suitable frame exists.

## Sample handles (creators.csv)

| Handle | Edge case |
|---|---|
| `@charlidamelio` | English baseline, face on camera |
| `@mrbeast` | English baseline, clear speech |
| `@gordonramsayofficial` | Face often off-camera (cooking) |
| `@khaby.lame` | Minimal/no speech — silent reaction videos |
| `@shakira` | Music-only, Spanish/English mix |
| `@katyperry` | Music-only, English |
| `@pewdiepie` | Multilingual (Swedish + English), gaming commentary |
| `@ibaillanos` | Spanish-only speaker |
| `@illojuan` | Spanish-only speaker, gaming |
| `@midudev` | Spanish-only speaker, face sometimes off-camera (coding tutorials) |

## Database schema (decided)

```
creators (
  handle          VARCHAR PRIMARY KEY,   -- e.g. "@username"
  spoken_languages JSON,                  -- e.g. ["en", "es"]
  hair_color      VARCHAR,                -- free-text from Moondream, or "unknown"
  status          ENUM(pending, processing, done, failed),
  processed_at    TIMESTAMP NULL
)

videos (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  creator_handle  VARCHAR,               -- FK → creators.handle
  tiktok_id       VARCHAR,
  tiktok_url      VARCHAR,
  status          ENUM(pending, downloaded, transcribed, classified, done, failed),
  transcript      TEXT NULL,
  audio_class     ENUM(speech, song, noise) NULL,
  frame_path      VARCHAR NULL,          -- relative path to stored .jpg, NULL until extracted
  error_message   TEXT NULL,
  created_at      TIMESTAMP,
  updated_at      TIMESTAMP
)
```

## Local LLM setup

- **Text model**: Llama 3.2 1B via Ollama — used for audio classification and language detection
- **Vision model**: Moondream via Ollama — used for hair color detection from a representative frame
- Both run CPU-only; HTTP timeouts for all Ollama calls must be 60–120 seconds to account for CPU inference and model-swap delay
