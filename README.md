# Laravel Log Monitor

View, search, filter and manage your Laravel log files from a modern, secure, dependency-free web interface.

`luiscamp/laravel-log-monitor` scans `storage/logs`, parses Monolog/Laravel-formatted entries (including multiline stack traces), and gives you a fast UI to browse, filter, paginate, copy, download and clear them — all without shipping a frontend build step.

> Conceptually inspired by [`rap2hpoutre/laravel-log-viewer`](https://github.com/rap2hpoutre/laravel-log-viewer), but implemented independently, with a modular architecture prepared for future Filament, Horizon and AI integrations.

## Screenshots

_Screenshots coming soon._

```
┌───────────────────────────────────────────────────────────────┐
│ Laravel Log Monitor                        production         │
├────────────────┬──────────────────────────────────────────────┤
│ ARCHIVOS       │ Buscar...                     Auto refresh   │
│                │                                              │
│ laravel.log    │ ERROR                     12:41:03           │
│ 2026-08-17     │ Undefined variable $user                     │
│ 2026-08-16     │ UserService.php:84                           │
│ queue.log      │                                              │
│ horizon.log    │ [Ver stack trace] [Copiar]                   │
│                │                                              │
├────────────────┴──────────────────────────────────────────────┤
│ ERROR 23 | WARNING 8 | INFO 125 | DEBUG 14                    │
└───────────────────────────────────────────────────────────────┘
```

## Requirements

- PHP >= 8.2
- Laravel 12 or 13
- Monolog (bundled with Laravel)

## Installation

```bash
composer require luiscamp/laravel-log-monitor
```

The service provider is registered automatically via Laravel package discovery.

### Publish the configuration

```bash
php artisan vendor:publish \
    --provider="Luiscamp\LaravelLogMonitor\LaravelLogMonitorServiceProvider" \
    --tag="config"
```

### Publish the views (optional)

```bash
php artisan vendor:publish \
    --provider="Luiscamp\LaravelLogMonitor\LaravelLogMonitorServiceProvider" \
    --tag="views"
```

## Routes

All routes are registered under the configurable prefix (`system/logs` by default) and are **never public**:

| Method | URI                              | Description                         |
|--------|-----------------------------------|--------------------------------------|
| GET    | `/system/logs`                   | Web UI                               |
| GET    | `/system/logs/files`             | JSON list of detected log files      |
| GET    | `/system/logs/{file}`            | JSON entries for a file (filtered)   |
| GET    | `/system/logs/{file}/download`   | Download a log file                  |
| POST   | `/system/logs/{file}/clear`      | Truncate a log file                  |
| POST   | `/system/logs/clear-all`         | Truncate every detected log file     |
| DELETE | `/system/logs/{file}`            | Permanently delete a log file        |
| DELETE | `/system/logs/delete-all`        | Permanently delete every log file    |

`{file}` is always a bare filename resolved against the configured logs directory — never a path.

The prefix (`system/logs`) is just config — change it freely:

```php
// config/log-monitor.php
'route' => [
    'prefix' => 'audit/logs', // routes now live under /audit/logs/*
    'middleware' => ['web', 'auth'],
],
```

## Authorization

By default, routes are protected by the `web` and `auth` middleware. You can customize the middleware stack in `config/log-monitor.php`:

```php
'route' => [
    'prefix' => 'system/logs',
    'middleware' => ['web', 'auth'],
],
```

For finer-grained control, define a Gate ability (checked automatically on every request):

```php
// AppServiceProvider
Gate::define('viewLaravelLogs', function ($user) {
    return $user->is_admin;
});
```

```php
// config/log-monitor.php
'authorization_gate' => 'viewLaravelLogs',
```

## Configuration

```php
return [
    'enabled' => env('LOG_MONITOR_ENABLED', true),
    'path' => storage_path('logs'),
    'route' => [
        'prefix' => 'system/logs',
        'middleware' => ['web', 'auth'],
    ],
    'allowed_extensions' => ['log'],
    'levels' => ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'],
    'pagination' => ['per_page' => 50, 'options' => [25, 50, 100, 250]],
    'allow_download' => true,
    'allow_clear' => false,
    'allow_delete' => false,
    'auto_refresh' => false,
    'auto_refresh_interval' => 10,
    'limits' => ['max_bytes_scanned' => 10 * 1024 * 1024, 'max_entries' => 5000],
    'authorization_gate' => 'viewLaravelLogs',
    'cache' => ['enabled' => true, 'ttl' => 5],
];
```

## Security

Path safety is the core design constraint of this package:

- The browser only ever sends a **bare filename** (`?file=laravel.log`), never a path.
- Every lookup resolves through `LogPathResolver`, which rejects `../`, absolute paths, null bytes, and anything that isn't a plain filename.
- The resolved **real path** (after following symlinks) is verified to still live inside the configured logs directory before any read, download or clear.
- Only extensions listed in `allowed_extensions` are ever served.
- Large files are never loaded fully into memory — reads are tail-bounded by `limits.max_bytes_scanned`.
- Clearing a file (single or all) requires `allow_clear => true`, a `POST` request, CSRF protection, and always truncates (`file_put_contents($file, '')`) rather than deleting files.
- Permanently deleting a file (single or all) is a **separate, stricter** opt-in: it requires `allow_delete => true` (disabled by default) plus a `DELETE` request and CSRF protection. It goes through the exact same `LogPathResolver` safety checks as every other action.
- No file outside the logs directory is ever read, and no `.env` values are exposed.

## Usage

Select a file from the sidebar (defaults to `laravel.log`, or the most recently modified file). Use the search box, level filter, and date range to narrow down entries; sort newest/oldest first; adjust the page size (25/50/100/250). Stack traces are collapsed by default — expand them or copy the whole formatted entry (handy for pasting into AI tools) with one click.

When `allow_clear` is enabled, the toolbar shows two destructive actions, both requiring confirmation: **Limpiar** (truncate the currently selected file) and **Vaciar todos** (truncate every detected log file in one request). Neither ever deletes a file from disk.

When `allow_delete` is also enabled, two additional, more destructive actions appear: **Eliminar archivo** and **Eliminar todos**, which permanently remove the file(s) from disk (`unlink`). Both require explicit confirmation. After a delete, the sidebar file list refreshes automatically and, if the currently viewed file was removed, the UI switches to another available file.

## Artisan commands

```bash
php artisan log-monitor:status
```

```
Laravel Log Monitor

Path: /var/www/app/storage/logs
Files: 7
Total size: 126 MB
Latest log: laravel-2026-08-17.log
```

```bash
php artisan log-monitor:clear {file?} [--all] [--force]
```

Clears the given file (or the default one), or every file with `--all`. Prompts for confirmation unless `--force` is passed, and refuses to run unless `allow_clear` is enabled.

```bash
php artisan log-monitor:delete {file?} [--all] [--force]
```

Permanently deletes the given file (or the default one) from disk, or every file with `--all`. Prompts for confirmation unless `--force` is passed, and refuses to run unless `allow_delete` is enabled.

## Testing

```bash
composer test
```

## Roadmap

**V2** — Filament plugin, Laravel Horizon integration, dashboard, charts, error timeline.

**V3** — AI Error Assistant (`[ Analizar con IA ]`): send exception/message/file/line/stack trace/framework/PHP version, receive probable cause, explanation, involved file, possible fix, and risk assessment.

## License

MIT. See [LICENSE](LICENSE).
