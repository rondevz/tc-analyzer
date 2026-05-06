# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A Laravel Zero CLI application — a Laravel-based micro-framework optimized for console apps. The entry point is the `application` binary (defined in `composer.json` `bin`).

# Project Overview

It is a "TikTok Creator Analysis Pipeline" that takes a CSV of handles, fetches videos via ScrapeCreators API, transcribes audio, and uses a local LLM (Ollama + Llama 3.2 1B) to detect spoken languages and hair color.

## Commands

```bash
# Run the application
php application <command>

# Run all tests
./vendor/bin/pest

# Run a single test file
./vendor/bin/pest tests/Feature/InspireCommandTest.php

# Run tests by name filter
./vendor/bin/pest --filter="inspire"

# Lint / fix code style (Laravel Pint)
./vendor/bin/pint

# Build a standalone PHAR (requires Box)
./vendor/bin/box compile
```

## Architecture

**Commands** live in `app/Commands/` and extend `LaravelZero\Framework\Commands\Command`. They are auto-discovered via `config/commands.php` (`'paths' => [app_path('Commands')]`). The `$signature` and `$description` properties define the CLI interface; `handle()` contains the logic. Commands can also define a `schedule()` method for task scheduling.

**Service providers** live in `app/Providers/` and are registered in `config/app.php`.

**Tests** use [Pest](https://pestphp.com/). Feature tests (in `tests/Feature/`) use the `Tests\TestCase` base class (bound via `tests/Pest.php`). Unit tests (in `tests/Unit/`) use the default PHPUnit test case.

**Building a PHAR**: `box.json` defines the build — it bundles `app/`, `bootstrap/`, `config/`, `vendor/`, and `composer.json` with GZ compression.

# Constraints
- Must handle partial failures gracefully (if one profile fails, continue to the next).
- Uses SQLite/MySQL via Eloquent.
- No heavy queues/workers, just a synchronous CLI pipeline.
