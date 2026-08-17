<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogClearTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_it_is_forbidden_by_default(): void
    {
        $this->putLog('laravel.log', 'content');

        $response = $this->post('/system/logs/laravel.log/clear');

        $response->assertForbidden();
    }

    public function test_it_truncates_the_file_when_enabled(): void
    {
        config(['log-monitor.allow_clear' => true]);

        $path = $this->putLog('laravel.log', 'content');

        $response = $this->post('/system/logs/laravel.log/clear');

        $response->assertOk();
        $this->assertSame('', file_get_contents($path));
    }

    public function test_it_does_not_delete_the_file(): void
    {
        config(['log-monitor.allow_clear' => true]);

        $path = $this->putLog('laravel.log', 'content');

        $this->post('/system/logs/laravel.log/clear');

        $this->assertFileExists($path);
    }

    public function test_get_requests_cannot_clear_the_file(): void
    {
        config(['log-monitor.allow_clear' => true]);

        $this->putLog('laravel.log', 'content');

        // The clear route only accepts POST; GET falls through to the
        // {file} show route instead of performing the destructive action.
        $response = $this->get('/system/logs/laravel.log/clear');

        $response->assertMethodNotAllowed();
    }
}
