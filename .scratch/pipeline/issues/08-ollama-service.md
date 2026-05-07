---
Status: needs-triage
---

# 08 — OllamaService

HTTP client wrapper for the Ollama API. Handles both text generation and vision (image) requests.

## Tasks

- `app/Services/OllamaService.php`
- Base URL from env: `OLLAMA_HOST` (default: `http://localhost:11434`)
- HTTP timeout: 120 seconds on all requests
- `generate(string $model, string $prompt): string` — POST `/api/generate`, `stream=false`, return `response.response`
- `generateWithImage(string $model, string $prompt, string $imagePath): string` — same but include `images: [base64_encode(file_get_contents($imagePath))]`
- Throw descriptive exception on non-2xx or missing `response` key

## Acceptance criteria

- Returns the model's response string on success
- Throws on HTTP error or malformed response
- Timeout is 120 seconds (verified in test)

## TDD notes

Unit test with `Http::fake()`. Test happy path for text, happy path for vision (with a tiny base64 fixture), and error response. Do not call real Ollama in tests.
