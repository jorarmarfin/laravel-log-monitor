<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Contracts;

use Luiscamp\LaravelLogMonitor\DTO\LogFile;

interface LogReaderInterface
{
    /**
     * Read up to $maxBytes from the tail of the given file.
     */
    public function tail(LogFile $file, int $maxBytes): string;
}
