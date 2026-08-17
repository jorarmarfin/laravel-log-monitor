<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Feature;

use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogDownloadTest extends TestCase
{
    public function test_it_downloads_an_allowed_log_file(): void
    {
        $this->putLog('laravel.log', "[2026-08-17 12:00:00] production.INFO: hello\n");

        $response = $this->get('/system/logs/laravel.log/download');

        $response->assertOk();
        $response->assertDownload('laravel.log');
    }

    public function test_it_returns_404_for_a_missing_file(): void
    {
        $response = $this->get('/system/logs/missing.log/download');

        $response->assertNotFound();
    }

    public function test_download_is_forbidden_when_disabled_in_config(): void
    {
        config(['log-monitor.allow_download' => false]);

        $this->putLog('laravel.log', 'content');

        $response = $this->get('/system/logs/laravel.log/download');

        $response->assertForbidden();
    }
}
