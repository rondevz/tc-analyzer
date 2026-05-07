---
Status: needs-triage
---

# 09 — AudioClassifierService

Classify a video transcript as `speech`, `song`, or `noise` using Llama 3.2 1B via OllamaService.

## Tasks

- `app/Services/AudioClassifierService.php`
- Inject `OllamaService`
- If transcript is empty string → return `noise` immediately (no LLM call)
- Otherwise: send a tightly constrained prompt asking Llama to output exactly one word: `speech`, `song`, or `noise`
- Parse the response, default to `noise` if response is unrecognisable
- Model: `llama3.2:1b`

## Prompt design (starting point — tune as needed)

```
Classify the following transcript as exactly one of: speech, song, noise.
Output only the single word. No explanation.

Transcript:
<transcript>
```

## Acceptance criteria

- Empty transcript → `noise` without calling Ollama
- Returns one of `speech`, `song`, `noise`
- Unrecognisable LLM response → defaults to `noise`

## TDD notes

Unit test with mocked OllamaService. Test: empty transcript, speech transcript, song lyrics, noisy/unrecognisable LLM response.
