---
Status: needs-triage
---

# 11 — HairColorDetectorService

Detect hair color from a representative frame `.jpg` using Moondream via OllamaService.

## Tasks

- `app/Services/HairColorDetectorService.php`
- Inject `OllamaService`
- If `$framePath` is null or file doesn't exist → return `'unknown'` immediately
- Call `OllamaService::generateWithImage('moondream', $prompt, $framePath)`
- Return the response string trimmed (e.g. `"dark brown"`, `"blonde"`, `"unknown"`)
- Model: `moondream`

## Prompt design (starting point)

```
Describe the hair color of the main person in this image in 2-5 words. If no person is visible or hair is not visible, respond with: unknown
```

## Acceptance criteria

- Null or missing frame path → `'unknown'` without calling Ollama
- Returns trimmed string from Moondream
- Works when Moondream returns `"unknown"` (no face/hair in frame)

## TDD notes

Unit test with mocked OllamaService. Test: null frame path, missing file, successful response, `"unknown"` response from model.
