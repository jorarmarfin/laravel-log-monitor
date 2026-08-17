<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Services;

use Carbon\CarbonImmutable;
use FilesystemIterator;
use Luiscamp\LaravelLogMonitor\DTO\LogFile;
use Luiscamp\LaravelLogMonitor\Support\LogPathResolver;

/**
 * Lists log files inside the configured, sandboxed logs directory.
 */
final class LogFileService
{
    /**
     * @param  array<int, string>  $allowedExtensions
     */
    public function __construct(
        private readonly string $baseDirectory,
        private readonly array $allowedExtensions,
        private readonly LogPathResolver $resolver,
    ) {}

    /**
     * @return array<int, LogFile>
     */
    public function all(): array
    {
        if (! is_dir($this->baseDirectory)) {
            return [];
        }

        $files = [];

        $iterator = new FilesystemIterator($this->baseDirectory, FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }

            $extension = strtolower($fileInfo->getExtension());

            if (! in_array($extension, $this->allowedExtensions, true)) {
                continue;
            }

            $files[] = $this->toDto($fileInfo->getFilename(), $fileInfo->getPathname(), $fileInfo->getSize(), $fileInfo->getMTime());
        }

        usort($files, fn (LogFile $a, LogFile $b) => $b->modifiedAt->timestamp <=> $a->modifiedAt->timestamp);

        return $files;
    }

    public function find(string $identifier): ?LogFile
    {
        $realPath = $this->resolver->resolve($identifier);

        $size = filesize($realPath);
        $mtime = filemtime($realPath);

        if ($size === false || $mtime === false) {
            return null;
        }

        return $this->toDto(basename($realPath), $realPath, $size, $mtime);
    }

    public function default(): ?LogFile
    {
        $all = $this->all();

        if ($all === []) {
            return null;
        }

        foreach ($all as $file) {
            if ($file->name === 'laravel.log') {
                return $file;
            }
        }

        return $all[0];
    }

    private function toDto(string $name, string $path, int $size, int $mtime): LogFile
    {
        return new LogFile(
            identifier: $name,
            name: $name,
            path: $path,
            size: $size,
            modifiedAt: CarbonImmutable::createFromTimestamp($mtime),
        );
    }
}
