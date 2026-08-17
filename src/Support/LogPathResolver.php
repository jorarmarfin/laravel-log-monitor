<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Support;

use Luiscamp\LaravelLogMonitor\Exceptions\InvalidLogFileException;

/**
 * Resolves a client-supplied log identifier into a real, on-disk path that
 * is guaranteed to live inside the configured logs directory.
 *
 * This is the single choke point responsible for preventing directory
 * traversal, absolute-path injection, and symlink escapes. Every entry
 * point (viewer, download, clear) must resolve through this class.
 */
final class LogPathResolver
{
    private readonly string $realBaseDirectory;

    /**
     * @param  array<int, string>  $allowedExtensions
     */
    public function __construct(
        private readonly string $baseDirectory,
        private readonly array $allowedExtensions,
    ) {
        $real = realpath($this->baseDirectory);

        $this->realBaseDirectory = $real !== false ? $real : $this->baseDirectory;
    }

    /**
     * Resolve an identifier (expected to be a bare filename) to a safe,
     * real, absolute path within the base directory.
     *
     * @throws InvalidLogFileException
     */
    public function resolve(string $identifier): string
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            throw InvalidLogFileException::traversalAttempt($identifier);
        }

        // Reject anything that isn't a plain filename: no directory
        // separators, no null bytes, no "..", no absolute paths.
        if (
            str_contains($identifier, "\0")
            || str_contains($identifier, '/')
            || str_contains($identifier, '\\')
            || str_contains($identifier, '..')
            || $identifier !== basename($identifier)
        ) {
            throw InvalidLogFileException::traversalAttempt($identifier);
        }

        $extension = strtolower((string) pathinfo($identifier, PATHINFO_EXTENSION));

        if (! in_array($extension, $this->allowedExtensions, true)) {
            throw InvalidLogFileException::disallowedExtension($identifier);
        }

        $candidate = rtrim($this->baseDirectory, '/').'/'.$identifier;

        if (! is_file($candidate)) {
            throw InvalidLogFileException::notFound($identifier);
        }

        $realCandidate = realpath($candidate);

        if ($realCandidate === false) {
            throw InvalidLogFileException::notFound($identifier);
        }

        // Guarantee the resolved real path (symlinks included) is still
        // physically inside the allowed base directory.
        if (! str_starts_with($realCandidate.DIRECTORY_SEPARATOR, $this->realBaseDirectory.DIRECTORY_SEPARATOR)) {
            throw InvalidLogFileException::outsideDirectory($identifier);
        }

        return $realCandidate;
    }

    public function baseDirectory(): string
    {
        return $this->realBaseDirectory;
    }
}
