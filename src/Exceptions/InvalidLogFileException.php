<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Exceptions;

use RuntimeException;

final class InvalidLogFileException extends RuntimeException
{
    public static function traversalAttempt(string $identifier): self
    {
        return new self("Invalid log file identifier: [{$identifier}].");
    }

    public static function notFound(string $identifier): self
    {
        return new self("Log file not found: [{$identifier}].");
    }

    public static function disallowedExtension(string $identifier): self
    {
        return new self("Log file extension is not allowed: [{$identifier}].");
    }

    public static function outsideDirectory(string $identifier): self
    {
        return new self("Log file resolves outside the allowed directory: [{$identifier}].");
    }
}
