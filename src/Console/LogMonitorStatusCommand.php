<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Console;

use Illuminate\Console\Command;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;

final class LogMonitorStatusCommand extends Command
{
    protected $signature = 'log-monitor:status';

    protected $description = 'Show a summary of the Laravel Log Monitor configuration and detected log files.';

    public function handle(LogRepositoryInterface $repository): int
    {
        $files = $repository->allFiles();
        $totalSize = array_sum(array_map(fn ($file) => $file->size, $files));
        $latest = $files[0] ?? null;

        $this->line('Laravel Log Monitor');
        $this->newLine();
        $this->line('Path: '.config('log-monitor.path'));
        $this->line('Files: '.count($files));
        $this->line('Total size: '.$this->humanSize($totalSize));
        $this->line('Latest log: '.($latest?->name ?? 'none'));

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2).' '.$units[$i];
    }
}
