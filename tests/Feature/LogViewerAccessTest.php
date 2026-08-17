<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogViewerAccessTest extends TestCase
{
    public function test_index_is_accessible_when_no_gate_is_defined(): void
    {
        $this->putLog('laravel.log', "[2026-08-17 12:00:00] production.INFO: hello\n");

        $response = $this->get('/system/logs');

        $response->assertOk();
    }

    public function test_index_is_denied_when_gate_denies_access(): void
    {
        Gate::define('viewLaravelLogs', fn () => false);

        $response = $this->get('/system/logs');

        $response->assertForbidden();
    }

    public function test_index_is_allowed_when_gate_allows_access(): void
    {
        Gate::define('viewLaravelLogs', fn ($user = null) => true);

        $this->putLog('laravel.log', "[2026-08-17 12:00:00] production.INFO: hello\n");

        $response = $this->get('/system/logs');

        $response->assertOk();
    }
}
