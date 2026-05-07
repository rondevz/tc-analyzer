---
Status: done
---

# 01 — Project foundation

Rename the binary, wire up Eloquent and MySQL, and establish the environment config. Everything else depends on this.

## Tasks

- Rename `application` binary file to `tc-analyzer` and update `composer.json` `bin` field
- Update `config/app.php` name to `tc-analyzer`
- Install the Laravel Zero database component (Eloquent + migrations): `php application app:install database`
- Add `config/database.php` pointing to MySQL via env vars
- Create `.env` (gitignored) and `.env.example` with all required keys:
  - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
  - `SCRAPECREATORS_API_KEY`
  - `OLLAMA_HOST` (default: `http://localhost:11434`)

## Acceptance criteria

- `php tc-analyzer` prints the command list without errors
- `php tc-analyzer migrate` runs without errors against a local MySQL DB
- `.env` is gitignored; `.env.example` is committed

## TDD notes

No unit tests for config wiring — verify manually by running the binary.
