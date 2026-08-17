<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Repositories;

use Luiscamp\LaravelLogMonitor\Contracts\LogReaderInterface;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;
use Luiscamp\LaravelLogMonitor\DTO\LogFile;
use Luiscamp\LaravelLogMonitor\Services\LogFileService;

final class FileLogRepository implements LogRepositoryInterface
{
    public function __construct(
        private readonly LogFileService $files,
        private readonly LogReaderInterface $reader,
        private readonly int $maxBytesScanned,
    ) {}

    public function allFiles(): array
    {
        return $this->files->all();
    }

    public function find(string $identifier): ?LogFile
    {
        return $this->files->find($identifier);
    }

    public function defaultFile(): ?LogFile
    {
        return $this->files->default();
    }

    public function contents(LogFile $file): string
    {
        return $this->reader->tail($file, $this->maxBytesScanned);
    }

    public function clear(LogFile $file): void
    {
        file_put_contents($file->path, '');
    }

    public function clearAll(): array
    {
        $files = $this->allFiles();

        foreach ($files as $file) {
            $this->clear($file);
        }

        return $files;
    }

    public function delete(LogFile $file): void
    {
        if (is_file($file->path)) {
            unlink($file->path);
        }
    }

    public function deleteAll(): array
    {
        $files = $this->allFiles();

        foreach ($files as $file) {
            $this->delete($file);
        }

        return $files;
    }
}
