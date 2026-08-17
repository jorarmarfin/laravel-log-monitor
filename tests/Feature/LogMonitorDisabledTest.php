<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Feature;

use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogMonitorDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('log-monitor.enabled', false);
    }

    public function test_routes_are_not_registered_when_package_is_disabled(): void
    {
        $response = $this->get('/system/logs');

        $response->assertNotFound();
    }
}
