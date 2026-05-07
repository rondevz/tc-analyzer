# TikTok Creator Analysis Pipeline

<div align="center">
  <img src="demo.png" alt="Demo" width="600">
</div>

A Laravel Zero CLI application that reads a CSV of TikTok handles, fetches recent videos, transcribes audio, and uses local LLMs (Ollama) to detect spoken languages and hair color.

```
php tc-analyzer scan creators.csv
```

---

## Setup

### Prerequisites

| Tool | Version | Purpose |
|------|---------|---------|
| PHP | 8.2+ | Runtime |
| Composer | 2.x | PHP dependencies |
| MySQL or SQLite | any recent | Persistence |
| [Ollama](https://ollama.com) | latest | Local LLM inference |
| [yt-dlp](https://github.com/yt-dlp/yt-dlp) | latest | Video download |
| [ffmpeg](https://ffmpeg.org) | 4.x+ | Audio extraction and frame capture |
| [whisper-cli](https://github.com/ggml-org/whisper.cpp) | latest | Speech transcription |

### Ollama models

```bash
ollama pull llama3.2:1b   # audio classification + language detection
ollama pull moondream     # hair color detection
```

### Install dependencies

```bash
composer install
```

### Environment

Copy `.env.example` to `.env` and configure:

```env
# Database (SQLite for local dev)
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# ScrapeCreators API key (https://scrapecreators.com)
SCRAPECREATORS_API_KEY=your_key_here

# Whisper — path to the compiled whisper-cli binary and model file
WHISPER_BINARY=whisper-cli
WHISPER_MODEL_PATH=/path/to/whisper/models/ggml-small.bin

# Ollama (defaults shown)
OLLAMA_HOST=http://localhost:11434
```

For SQLite, create the database file first:

```bash
touch database/database.sqlite
```

### Run migrations

```bash
php tc-analyzer migrate
```

### Run the pipeline

```bash
# Process all 10 handles in creators.csv
php tc-analyzer scan creators.csv

# Process only the first 2 handles (useful for demos)
php tc-analyzer scan creators.csv --limit=2

# Keep downloaded .mp4 files after processing
php tc-analyzer scan creators.csv --keep-videos
```

---

## Architecture

The `scan` command orchestrates a sequential pipeline. Each creator in the CSV is processed independently; a failure on one does not abort the rest.

```
creators.csv
    │
    ▼
ScrapeCreatorsService        ← fetches up to 3 recent video URLs via ScrapeCreators API
    │
    ▼  (per video)
VideoDownloaderService       ← downloads .mp4 via yt-dlp
TranscriptionService         ← extracts audio with ffmpeg, transcribes with whisper-cli
AudioClassifierService       ← classifies transcript as speech / song / noise (Llama 3.2 1B)
FrameExtractorService        ← extracts a .jpg at 30% duration with ffmpeg (speech videos only)
    │
    ▼  (per creator, after all videos)
LanguageDetectorService      ← detects ISO 639-1 language codes from speech transcripts (Llama 3.2 1B)
HairColorDetectorService     ← describes hair color from representative frame (Moondream)
    │
    ▼
creators table               ← spoken_languages (JSON), hair_color, status = done
```

**Status columns** on both `creators` and `videos` make the pipeline resumable and idempotent: re-running the command skips creators and videos already marked `done`.

**File lifecycle** — after processing, `.mp4` and intermediate `.wav` files are deleted by default. Representative frames (`.jpg`) are kept permanently under `storage/frames/<handle>/`. Pass `--keep-videos` to retain the video files.

---

## Decisions

### Local LLM placement (ADR-0001)

Two local models are used:

- **Llama 3.2 1B** (text) — audio classification and language detection from transcripts. Whisper already emits per-segment language tags, but they are noisy and per-segment. Llama reasons holistically across all transcripts for a creator and returns a clean JSON array of ISO 639-1 codes.
- **Moondream** (vision) — hair color detection from a still frame. A hosted vision API (e.g. GPT-4o) would be more accurate, but the assessment requires a locally-run LLM. Hair color is the fuzziest step, making it the most defensible placement for a local model.

A single multimodal model (e.g. LLaVA 7B) was considered but rejected — reliable text reasoning requires 7B+ parameters, which is too slow on CPU for a demo-able pipeline.

### Video download strategy (ADR-0002)

ScrapeCreators charges 10 credits per video with `download_media=true`. At 10 creators × 3 videos = 30 videos, that would cost 300 credits against a 100-credit free tier. Instead, metadata is fetched at 1 credit per creator (~10 credits total) and the TikTok page URL is passed to `yt-dlp` for the actual download. `yt-dlp` handles TikTok CDN resolution natively and is invoked as a subprocess, consistent with how Whisper and ffmpeg are called.

### 3 videos per creator

Three videos give enough signal for language detection without excessive API credit consumption or processing time. A single video risks being atypical (a collab, a repost); three provides a lightweight majority signal.

### Whisper `small` model

The `small` model (244M parameters) balances transcription accuracy with CPU inference speed. The `base` model is faster but misses accents and non-English speech. The `medium`/`large` models are significantly slower on CPU and unnecessary for language detection purposes.

---

## Edge cases

| Scenario | How the pipeline handles it |
|----------|-----------------------------|
| No speech in videos (silent / music) | `AudioClassifierService` returns `noise` or `song`; those videos are excluded from language detection. `spoken_languages` will be `[]`. |
| No face or hair visible in frame | `HairColorDetectorService` returns `"unknown"` when Moondream can't identify a person. |
| ScrapeCreators API error | `ScrapeCreatorsService` throws `RuntimeException`; the creator is marked `failed` and the next handle is processed. |
| yt-dlp download failure | `VideoDownloaderService` throws; the video is marked `failed` with the error message saved. The creator continues processing its remaining videos. |
| Empty transcript | `AudioClassifierService` short-circuits to `noise` without calling Ollama. |
| Malformed JSON from Llama | `LanguageDetectorService` returns `[]` and logs a warning. |
| Missing frame file | `HairColorDetectorService` guards on `file_exists` and returns `"unknown"` immediately. |

---

## Known limitations

- **CPU inference speed** — both Llama 3.2 1B and Moondream run CPU-only via Ollama. Expect 10–60 seconds per LLM call depending on hardware. A full 10-creator run can take 20–40 minutes on a modern laptop.
- **yt-dlp fragility** — TikTok periodically rotates CDN URLs and adjusts bot-detection. If yt-dlp starts failing at demo time, the fallback is to enable `download_media=true` on ScrapeCreators for the affected creator and accept the 10-credit cost.
- **Hair color accuracy** — Moondream is a compact vision model. Results degrade with poor lighting, hats, motion blur, or when the creator's face is off-camera (common for cooking and coding content).
- **Language detection on short transcripts** — three 30-second videos may not yield enough text for reliable multilingual detection. Creators who mix languages within a single sentence (code-switching) may be detected as monolingual.
- **No GPU support** — the pipeline is synchronous and single-threaded. Running on a machine with a CUDA-capable GPU and configuring Ollama to use it would reduce inference time by 10–50×.
