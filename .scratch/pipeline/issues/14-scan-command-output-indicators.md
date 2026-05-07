---
Status: needs-triage
---

# 14 — ScanCommand output indicators

Add rich terminal output to `ScanCommand` so that every pipeline step is visible during a demo run.

## Decisions (from design session)

### Granularity
All pipeline steps are surfaced — not just creator/video level. Rationale: the command is used for live demonstrations where transparency at every stage is more valuable than a clean terminal.

### Visual style
Laravel Zero's built-in `task()` component was evaluated but rejected for steps that need to show a result value — it renders a fixed `DONE`/`FAIL` suffix with no way to append dynamic text on the same line.

Instead, a private `step()` helper was implemented directly on `ScanCommand`:
- Writes the step label + fill dots while the operation runs (terminal stays active during 30–60s LLM calls)
- Appends `✓ <result>` or `✗ <error>` on the same line once the callable returns or throws

### Result suffixes per step

| Step | Suffix shown after ✓ |
|------|----------------------|
| Fetching videos | `3 video(s) found` |
| Downloading | *(none)* |
| Transcribing | First 80 chars of transcript in quotes, e.g. `"Hello everyone, welcome back…"` |
| Classifying | Audio class: `speech`, `song`, or `noise` |
| Extracting frame | *(none — path not shown to keep output clean)* |
| Detecting languages | Comma-separated ISO codes, e.g. `en, es` |
| Detecting hair color | Raw Moondream response, e.g. `dark brown` |

Transcript shown as a truncated snippet (not word count) so demo viewers can see the actual language in context.

### Frame extraction line
- Shown only when `audio_class === 'speech'`
- If a frame was already extracted for the creator (earlier video), prints `Frame already extracted, skipping` in gray instead
- Not shown at all for `song` / `noise` videos

### Per-creator summary
A summary line is printed after each creator completes:
```
─────────────────────────────────────────────────────────────
@handle · 3 done · languages: en · hair: dark brown
```
Shows: video done/failed counts, detected languages, hair color.

### Already-done creators
Prints `↷ Already done, skipping` in yellow and returns early — no service calls made.

## Output format (example)

```
@charlidamelio [1/10]
  Fetching videos ............................ ✓ 3 video(s) found

  Video 1/3 7234891234
    Downloading ............................. ✓
    Transcribing ............................ ✓ "Hey guys welcome back to my channel..."
    Classifying ............................. ✓ speech
    Extracting frame ........................ ✓

  Video 2/3 7234891235
    Downloading ............................. ✓
    Transcribing ............................ ✓ "So the first thing you want to do is..."
    Classifying ............................. ✓ speech
    Frame already extracted, skipping

  Video 3/3 7234891236
    Downloading ............................. ✓
    Transcribing ............................ ✓ empty
    Classifying ............................. ✓ noise

  Detecting languages ........................ ✓ en
  Detecting hair color ....................... ✓ dark brown

────────────────────────────────────────────────────────────
@charlidamelio · 3 done · languages: en · hair: dark brown
```

## Implementation

`step(string $description, callable $fn, ?callable $format = null): mixed` in `app/Commands/ScanCommand.php`.
- Strips color tags for dot-fill width calculation
- Re-throws on failure (outer try/catch in `processVideo` handles DB state)
- `processVideo` catch no longer prints warnings — `step()` handles visual error output
