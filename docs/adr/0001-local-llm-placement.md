# Local LLM placement: Llama 3.2 1B for language detection, Moondream for hair color

The assessment requires at least one local LLM step. We use two: Llama 3.2 1B (via Ollama) for audio classification and language detection from transcripts, and Moondream (via Ollama) for hair color detection from a still frame. Both run CPU-only with 60–120s HTTP timeouts to accommodate model-swap delay.

## Considered options

**Language detection only (one LLM step):** Whisper already outputs a detected language per segment, so language detection could be done without a local LLM at all. We use Llama anyway because it lets us reason across all transcripts for a creator holistically and produce a clean JSON array — Whisper's per-segment language tags are noisy and require aggregation logic.

**Hosted vision API for hair color (e.g. GPT-4o):** More accurate under poor lighting, handles multiple people in frame better. Rejected because the assessment explicitly requires a locally-run LLM and hair color is the fuzziest step — putting the local LLM here is the most defensible placement for the walkthrough conversation.

**Single vision model for everything (e.g. LLaVA):** A multimodal model could handle both transcript reasoning and frame analysis. Rejected because a 7B+ vision model is required for reliable text reasoning, which is too slow on CPU for a demo-able pipeline.
