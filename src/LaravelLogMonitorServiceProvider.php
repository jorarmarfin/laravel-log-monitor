<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Luiscamp\LaravelLogMonitor\Console\LogMonitorClearCommand;
use Luiscamp\LaravelLogMonitor\Console\LogMonitorDeleteCommand;
use Luiscamp\LaravelLogMonitor\Console\LogMonitorStatusCommand;
use Luiscamp\LaravelLogMonitor\Contracts\LogParserInterface;
use Luiscamp\LaravelLogMonitor\Contracts\LogReaderInterface;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;
use Luiscamp\LaravelLogMonitor\Http\Middleware\AuthorizeLogViewer;
use Luiscamp\LaravelLogMonitor\Repositories\FileLogRepository;
use Luiscamp\LaravelLogMonitor\Services\LogFileService;
use Luiscamp\LaravelLogMonitor\Services\LogParserService;
use Luiscamp\LaravelLogMonitor\Support\LogPathResolver;
use Luiscamp\LaravelLogMonitor\Support\TailFileReader;

final class LaravelLogMonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/log-monitor.php', 'log-monitor');

        $this->app->singleton(LogPathResolver::class, function ($app) {
            return new LogPathResolver(
                baseDirectory: (string) $app['config']->get('log-monitor.path'),
                allowedExtensions: (array) $app['config']->get('log-monitor.allowed_extensions', ['log']),
            );
        });

        $this->app->singleton(LogFileService::class, function ($app) {
            return new LogFileService(
                baseDirectory: (string) $app['config']->get('log-monitor.path'),
                allowedExtensions: (array) $app['config']->get('log-monitor.allowed_extensions', ['log']),
                resolver: $app->make(LogPathResolver::class),
            );
        });

        $this->app->bind(LogReaderInterface::class, TailFileReader::class);
        $this->app->bind(LogParserInterface::class, LogParserService::class);

        $this->app->singleton(LogRepositoryInterface::class, function ($app) {
            return new FileLogRepository(
                files: $app->make(LogFileService::class),
                reader: $app->make(LogReaderInterface::class),
                maxBytesScanned: (int) $app['config']->get('log-monitor.limits.max_bytes_scanned', 10 * 1024 * 1024),
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'log-monitor');

        $this->registerRoutes();

        $this->publishes([
            __DIR__.'/../config/log-monitor.php' => config_path('log-monitor.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/log-monitor'),
        ], 'views');

        if ($this->app->runningInConsole()) {
            $this->commands([
                LogMonitorStatusCommand::class,
                LogMonitorClearCommand::class,
                LogMonitorDeleteCommand::class,
            ]);
        }
    }

    private function registerRoutes(): void
    {
        if (! (bool) config('log-monitor.enabled', true)) {
            return;
        }

        $middleware = (array) config('log-monitor.route.middleware', ['web', 'auth']);
        $middleware[] = AuthorizeLogViewer::class;

        Route::group([
            'prefix' => config('log-monitor.route.prefix', 'system/logs'),
            'middleware' => $middleware,
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}
