<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Contracts;

use Luiscamp\LaravelLogMonitor\DTO\LogEntry;

interface LogParserInterface
{
    /**
     * Parse a raw log blob into structured, strongly typed entries.
     *
     * @return array<int, LogEntry>
     */
    public function parse(string $raw): array;
}
