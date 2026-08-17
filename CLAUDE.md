# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

This repository currently contains only `SPEC_Laravel_Log_Monitor.md` — no code has been written yet. **That spec file is the authoritative requirements document for this project and must be read in full before implementing anything.** Everything below summarizes it; when in doubt, defer to the spec itself.

## What this is

A Composer package for Laravel, named `luiscamp/laravel-log-monitor` (namespace `Luiscamp\LaravelLogMonitor`, matching the author's other package `luiscamp/laravel-peru-ubigeo`), that lets developers view, search, filter, and manage Laravel log files (`storage/logs/*.log`) from a web UI. Conceptually inspired by `rap2hpoutre/laravel-log-viewer` but must be an original, modular implementation — do not copy code, docs, class names, or assets from that package.

Target compatibility: PHP >= 8.2, Laravel 12 and 13, Monolog-formatted logs, Linux production environments.

## Build & test commands

No `composer.json` exists yet. Per the spec, the package must ship with:
- `composer test` — runs the PHPUnit suite (set this up as part of implementation)
- Optionally Laravel Pint (formatting) and PHPStan (static analysis), fully configured if added

Once scaffolded, tests live under `tests/Unit/` and `tests/Feature/`; run a single test the standard PHPUnit way (e.g. `vendor/bin/phpunit --filter=TestName`).

## Architecture (per spec §4)

```
src/
├── Contracts/        LogReaderInterface, LogParserInterface, LogRepositoryInterface
├── DTO/               LogEntry, LogFile (final readonly classes, no arbitrary arrays)
├── Exceptions/
├── Http/
│   ├── Controllers/  LogViewerController, LogDownloadController, LogClearController
│   └── Middleware/   AuthorizeLogViewer
├── Services/          LogFileService, LogParserService, LogSearchService
├── Repositories/      FileLogRepository
├── Support/
└── LaravelLogMonitorServiceProvider.php

config/log-monitor.php
resources/views/{layout,index}.blade.php + components/
routes/web.php
tests/{Unit,Feature}/
```

The core separation to preserve: **file reading**, **parsing**, **search**, **presentation**, and **authorization** are distinct layers — don't collapse them even though the spec explicitly says to avoid overengineering/unnecessary abstractions elsewhere.

## Critical constraints

- **Path safety is the top priority.** Never accept a raw absolute path or filename from the client (e.g. `?file=/etc/passwd`). Always resolve requested files via sanitized identifiers against the configured log directory, verify the resolved real path stays inside it, and guard against `../`, absolute paths, and malicious symlinks. Extensions are restricted to `config('log-monitor.allowed_extensions')`.
- **Never load entire files into memory.** No bare `file_get_contents($hugeFile)`. Use block reads, iterators, `SplFileObject`, tail-from-end reads, and configurable limits — the package must stay stable on multi-hundred-MB logs.
- **Multiline log entries** (stack traces) must parse as a single `LogEntry`, not one entry per line.
- **Destructive actions** (clearing a log) require POST/DELETE — never GET — plus CSRF protection and a confirmation step. Prefer truncating (`file_put_contents($file, '')`) over deleting the file, and only when `allow_clear` is enabled.
- **Authorization is closed by default.** Routes are never public; default middleware is `['web', 'auth']`, configurable, plus an optional `Gate::define('viewLaravelLogs', ...)` extension point (`authorization_gate` config key).
- Must function correctly under `APP_ENV=production`, not just locally — never leak `.env` values or read files outside the logs directory.

## Config shape (`config/log-monitor.php`)

Key options: `enabled`, `path` (defaults to `storage_path('logs')`), `route.prefix`/`route.middleware`, `allowed_extensions`, `levels`, `pagination.per_page`, `allow_download`, `allow_clear`, `auto_refresh`, `auto_refresh_interval`, `authorization_gate`. All sensitive functionality must be toggleable from config.

## Implementation order (per spec §41)

Follow this sequence; do not build the complex UI before log reading/parsing is covered by tests:

1. `composer.json` (with `extra.laravel.providers` for package discovery)
2. Service Provider (config merge, view/route loading, publishes, command registration)
3. `config/log-monitor.php`
4. DTOs (`LogEntry`, `LogFile`)
5. `LogFileService`
6. `LogParserService`
7. Repository (`FileLogRepository`)
8. Controllers
9. Routes (`GET /system/logs`, `/system/logs/files`, `/system/logs/{file}`, per §26)
10. Views (no forced Bootstrap; Tailwind allowed only if it doesn't force consumers to recompile assets; prefer no frontend build step for V1)
11. Search & filters (level, text, file, date — searching message + stack trace + context)
12. Download/clear actions
13. Auto refresh (fetch/polling to a JSON endpoint, no page reload, no WebSockets in V1)
14. Tests
15. README

Also required: `php artisan log-monitor:status` command (and optionally `log-monitor:clear`, which must prompt for confirmation).

## Out of scope for V1

Filament, Horizon, WebSockets, mandatory Redis, database storage of logs, AI-based error analysis (OpenAI/Claude/Gemini), Slack/Telegram notifications, Sentry, multi-server support — but design the code so these can be added later without rearchitecting (see spec §37–38 for the V2/V3 roadmap).

## License

MIT — an accompanying `LICENSE` file is expected.
