---
Status: needs-triage
---

# 02 — Database migrations

Create the two migrations that define the schema agreed in CONTEXT.md.

## Tasks

- `database/migrations/xxxx_create_creators_table.php`: handle (PK), spoken_languages (JSON), hair_color, status ENUM, processed_at, timestamps
- `database/migrations/xxxx_create_videos_table.php`: id, creator_handle (FK), tiktok_id, tiktok_url, status ENUM, transcript, audio_class ENUM, frame_path, error_message, timestamps

## Acceptance criteria

- `php tc-analyzer migrate` creates both tables cleanly on a fresh DB
- Re-running `migrate` is a no-op (idempotent)
- Schema matches CONTEXT.md exactly — no extra columns, no missing ones

## TDD notes

No unit test needed. Verify with `SHOW CREATE TABLE creators\G` and `SHOW CREATE TABLE videos\G` after migrating.
