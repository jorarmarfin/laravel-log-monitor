<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_it_is_forbidden_by_default(): void
    {
        $path = $this->putLog('laravel.log', 'content');

        $response = $this->delete('/system/logs/laravel.log');

        $response->assertForbidden();
        $this->assertFileExists($path);
    }

    public function test_it_deletes_the_file_from_disk_when_enabled(): void
    {
        config(['log-monitor.allow_delete' => true]);

        $path = $this->putLog('laravel.log', 'content');

        $response = $this->deleteJson('/system/logs/laravel.log');

        $response->assertOk();
        $this->assertFileDoesNotExist($path);
    }

    public function test_it_returns_404_for_a_missing_file(): void
    {
        config(['log-monitor.allow_delete' => true]);

        $response = $this->deleteJson('/system/logs/missing.log');

        $response->assertNotFound();
    }

    public function test_it_rejects_directory_traversal(): void
    {
        config(['log-monitor.allow_delete' => true]);

        $response = $this->deleteJson('/system/logs/..%2F..%2Fetc%2Fpasswd');

        $response->assertNotFound();
    }

    public function test_get_requests_cannot_delete_the_file(): void
    {
        config(['log-monitor.allow_delete' => true]);

        $path = $this->putLog('laravel.log', 'content');

        $response = $this->get('/system/logs/laravel.log');

        $response->assertOk();
        $this->assertFileExists($path);
    }
}
