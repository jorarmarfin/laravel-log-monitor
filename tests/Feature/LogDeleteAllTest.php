<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogDeleteAllTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_it_is_forbidden_by_default(): void
    {
        $path = $this->putLog('laravel.log', 'content');

        $response = $this->delete('/system/logs/delete-all');

        $response->assertForbidden();
        $this->assertFileExists($path);
    }

    public function test_it_deletes_every_log_file_when_enabled(): void
    {
        config(['log-monitor.allow_delete' => true]);

        $laravel = $this->putLog('laravel.log', 'content');
        $worker = $this->putLog('worker.log', 'more content');

        $response = $this->deleteJson('/system/logs/delete-all');

        $response->assertOk();
        $this->assertFileDoesNotExist($laravel);
        $this->assertFileDoesNotExist($worker);
    }

    public function test_it_reports_the_deleted_files(): void
    {
        config(['log-monitor.allow_delete' => true]);

        $this->putLog('laravel.log', 'content');
        $this->putLog('worker.log', 'more content');

        $response = $this->deleteJson('/system/logs/delete-all');

        $response->assertJsonCount(2, 'deleted');
    }

    public function test_clearing_all_does_not_delete_files(): void
    {
        config(['log-monitor.allow_clear' => true, 'log-monitor.allow_delete' => false]);

        $path = $this->putLog('laravel.log', 'content');

        $this->postJson('/system/logs/clear-all');

        $this->assertFileExists($path);
    }
}
