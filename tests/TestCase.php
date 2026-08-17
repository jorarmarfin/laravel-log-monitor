<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests;

use Luiscamp\LaravelLogMonitor\LaravelLogMonitorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelLogMonitorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('log-monitor.path', $this->logsPath());
        $app['config']->set('log-monitor.route.middleware', ['web']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cleanLogsPath();
    }

    protected function tearDown(): void
    {
        $this->cleanLogsPath();

        parent::tearDown();
    }

    protected function logsPath(): string
    {
        $path = sys_get_temp_dir().'/log-monitor-tests';

        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    private function cleanLogsPath(): void
    {
        $path = $this->logsPath();

        foreach (glob($path.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    protected function putLog(string $filename, string $contents): string
    {
        $path = $this->logsPath().'/'.$filename;
        file_put_contents($path, $contents);

        return $path;
    }
}
