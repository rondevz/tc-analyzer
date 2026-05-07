---
Status: needs-triage
---

# 13 — Sample data and README

Create the sample `creators.csv` and write the `README.md` covering setup, architecture, decisions, and known limitations.

## Tasks

### creators.csv

Create at repo root with the 10 agreed handles (see CONTEXT.md Sample handles section).

```
handle
@charlidamelio
@khaby.lame
@gordonramsayofficial
@shakira
@mrbeast
@ibaillanos
@katyperry
@pewdiepie
@illojuan
@midudev
```

### README.md

Sections required by the assessment:

1. **Setup** — prerequisites (PHP 8.2, Composer, MySQL, Ollama with `llama3.2:1b` + `moondream`, `yt-dlp`, `ffmpeg`, `whisper`), env config, how to run end-to-end
2. **Architecture** — short walkthrough of the pipeline steps and which component handles each
3. **Decisions** — local LLM placement (reference ADR-0001), yt-dlp download strategy (reference ADR-0002), 3 videos per creator rationale, Whisper `small` model choice
4. **Edge cases** — how the pipeline handles: no speech, no face, API errors, yt-dlp failure, empty transcript
5. **Known limitations** — CPU inference speed, yt-dlp fragility, hair color accuracy under poor lighting

## Acceptance criteria

- `creators.csv` has header row `handle` and 10 handles
- README covers all five sections above
- Setup section can be followed by someone with a fresh machine

## TDD notes

No tests. Manual review only.
