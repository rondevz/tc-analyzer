---
Status: needs-triage
---

# 03 — Eloquent models

Create `Creator` and `Video` models with correct casts, fillable fields, and the one relationship between them.

## Tasks

- `app/Models/Creator.php`
  - `$primaryKey = 'handle'`, `$keyType = 'string'`, `$incrementing = false`
  - Cast `spoken_languages` as `array`
  - Cast `status` as `string`
  - `hasMany(Video::class, 'creator_handle')`
- `app/Models/Video.php`
  - `$fillable` covering all pipeline-written columns
  - `belongsTo(Creator::class, 'creator_handle')`

## Acceptance criteria

- `Creator::create(['handle' => '@test', 'status' => 'pending'])` persists and retrieves correctly
- `$creator->videos()` returns the correct relation
- `spoken_languages` round-trips as a PHP array through JSON cast

## TDD notes

Write a unit test (`tests/Unit/Models/`) for the JSON cast and the relation. Use an in-memory SQLite DB or a test MySQL DB — not the dev DB.
