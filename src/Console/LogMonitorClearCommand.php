<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Console;

use Illuminate\Console\Command;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;
use Luiscamp\LaravelLogMonitor\Exceptions\InvalidLogFileException;

final class LogMonitorClearCommand extends Command
{
    protected $signature = 'log-monitor:clear {file? : The log file name to clear} {--all : Clear every detected log file} {--force : Skip the confirmation prompt}';

    protected $description = 'Clear (truncate) a Laravel log file.';

    public function handle(LogRepositoryInterface $repository): int
    {
        if (! (bool) config('log-monitor.allow_clear', false)) {
            $this->error('Clearing log files is disabled (log-monitor.allow_clear).');

            return self::FAILURE;
        }

        if ($this->option('all')) {
            return $this->clearAll($repository);
        }

        $identifier = $this->argument('file');

        try {
            $file = $identifier !== null
                ? $repository->find((string) $identifier)
                : $repository->defaultFile();
        } catch (InvalidLogFileException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($file === null) {
            $this->error('Log file not found.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("¿Seguro que deseas vaciar [{$file->name}]?")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $repository->clear($file);

        $this->info("Log file [{$file->name}] cleared.");

        return self::SUCCESS;
    }

    private function clearAll(LogRepositoryInterface $repository): int
    {
        $files = $repository->allFiles();

        if ($files === []) {
            $this->info('No log files found.');

            return self::SUCCESS;
        }

        $names = implode(', ', array_map(fn ($file) => $file->name, $files));

        if (! $this->option('force') && ! $this->confirm("¿Seguro que deseas vaciar TODOS los archivos de log ({$names})?")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $cleared = $repository->clearAll();

        $this->info(count($cleared).' log file(s) cleared.');

        return self::SUCCESS;
    }
}
