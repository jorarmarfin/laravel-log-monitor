<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Console;

use Illuminate\Console\Command;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;
use Luiscamp\LaravelLogMonitor\Exceptions\InvalidLogFileException;

final class LogMonitorDeleteCommand extends Command
{
    protected $signature = 'log-monitor:delete {file? : The log file name to delete} {--all : Delete every detected log file} {--force : Skip the confirmation prompt}';

    protected $description = 'Permanently delete a Laravel log file from disk.';

    public function handle(LogRepositoryInterface $repository): int
    {
        if (! (bool) config('log-monitor.allow_delete', false)) {
            $this->error('Deleting log files is disabled (log-monitor.allow_delete).');

            return self::FAILURE;
        }

        if ($this->option('all')) {
            return $this->deleteAll($repository);
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

        if (! $this->option('force') && ! $this->confirm("¿Seguro que deseas ELIMINAR [{$file->name}] del disco? Esta acción no se puede deshacer.")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $repository->delete($file);

        $this->info("Log file [{$file->name}] deleted.");

        return self::SUCCESS;
    }

    private function deleteAll(LogRepositoryInterface $repository): int
    {
        $files = $repository->allFiles();

        if ($files === []) {
            $this->info('No log files found.');

            return self::SUCCESS;
        }

        $names = implode(', ', array_map(fn ($file) => $file->name, $files));

        if (! $this->option('force') && ! $this->confirm("¿Seguro que deseas ELIMINAR TODOS los archivos de log del disco ({$names})? Esta acción no se puede deshacer.")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $deleted = $repository->deleteAll();

        $this->info(count($deleted).' log file(s) deleted.');

        return self::SUCCESS;
    }
}
