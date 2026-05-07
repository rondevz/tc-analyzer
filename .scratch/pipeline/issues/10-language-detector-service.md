---
Status: done
---

# 10 — LanguageDetectorService

Detect the spoken language(s) for a creator from the concatenated transcripts of all their `speech`-classified videos, using Llama 3.2 1B via OllamaService.

## Tasks

- `app/Services/LanguageDetectorService.php`
- Inject `OllamaService`
- Accept an array of transcript strings (one per speech video)
- If array is empty → return `[]` immediately (no LLM call)
- Concatenate transcripts, send prompt asking for a JSON array of ISO 639-1 language codes (e.g. `["en", "es"]`)
- Parse response with `json_decode`; validate each entry is a 2-letter string
- On parse failure → return `[]` and log a warning
- Model: `llama3.2:1b`

## Prompt design (starting point)

```
Identify all spoken languages in the following text. Return a JSON array of ISO 639-1 codes only (e.g. ["en", "es"]). No explanation.

Text:
<transcripts>
```

## Acceptance criteria

- Empty input → `[]` without calling Ollama
- Returns a valid PHP array of ISO 639-1 codes
- Handles multilingual transcripts correctly
- Malformed JSON from LLM → returns `[]`

## TDD notes

Unit test with mocked OllamaService. Test: empty input, English-only, Spanish-only, mixed English+Spanish, malformed JSON response.
