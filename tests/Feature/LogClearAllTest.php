<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogClearAllTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_it_is_forbidden_by_default(): void
    {
        $this->putLog('laravel.log', 'content');

        $response = $this->post('/system/logs/clear-all');

        $response->assertForbidden();
    }

    public function test_it_truncates_every_log_file_when_enabled(): void
    {
        config(['log-monitor.allow_clear' => true]);

        $laravel = $this->putLog('laravel.log', 'content');
        $worker = $this->putLog('worker.log', 'more content');

        $response = $this->postJson('/system/logs/clear-all');

        $response->assertOk();
        $this->assertSame('', file_get_contents($laravel));
        $this->assertSame('', file_get_contents($worker));
    }

    public function test_it_does_not_delete_any_file(): void
    {
        config(['log-monitor.allow_clear' => true]);

        $laravel = $this->putLog('laravel.log', 'content');
        $worker = $this->putLog('worker.log', 'more content');

        $this->postJson('/system/logs/clear-all');

        $this->assertFileExists($laravel);
        $this->assertFileExists($worker);
    }

    public function test_it_reports_the_cleared_files(): void
    {
        config(['log-monitor.allow_clear' => true]);

        $this->putLog('laravel.log', 'content');
        $this->putLog('worker.log', 'more content');

        $response = $this->postJson('/system/logs/clear-all');

        $response->assertJsonCount(2, 'cleared');
    }

    public function test_get_requests_do_not_trigger_the_bulk_clear_action(): void
    {
        config(['log-monitor.allow_clear' => true]);

        $laravel = $this->putLog('laravel.log', 'content');

        // "clear-all" has no valid log extension, so GET falls through to
        // the {file} show route and is rejected by LogPathResolver instead
        // of ever performing the destructive action.
        $response = $this->get('/system/logs/clear-all');

        $response->assertNotFound();
        $this->assertSame('content', file_get_contents($laravel));
    }
}
