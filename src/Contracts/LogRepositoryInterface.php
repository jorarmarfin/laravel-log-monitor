<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Contracts;

use Luiscamp\LaravelLogMonitor\DTO\LogFile;

interface LogRepositoryInterface
{
    /**
     * @return array<int, LogFile>
     */
    public function allFiles(): array;

    public function find(string $identifier): ?LogFile;

    public function defaultFile(): ?LogFile;

    public function contents(LogFile $file): string;

    public function clear(LogFile $file): void;

    /**
     * Truncate every detected log file. Files are emptied, never deleted.
     *
     * @return array<int, LogFile>
     */
    public function clearAll(): array;

    /**
     * Permanently remove a log file from disk.
     */
    public function delete(LogFile $file): void;

    /**
     * Permanently remove every detected log file from disk.
     *
     * @return array<int, LogFile>
     */
    public function deleteAll(): array;
}
