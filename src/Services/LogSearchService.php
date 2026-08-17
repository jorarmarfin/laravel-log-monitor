<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Services;

use Carbon\CarbonImmutable;
use Luiscamp\LaravelLogMonitor\DTO\LogEntry;

/**
 * Filters, sorts and summarizes already-parsed LogEntry collections.
 * Deliberately decoupled from file reading/parsing.
 */
final class LogSearchService
{
    /**
     * @param  array<int, LogEntry>  $entries
     * @return array<int, LogEntry>
     */
    public function filter(
        array $entries,
        ?string $level = null,
        ?string $search = null,
        ?string $from = null,
        ?string $to = null,
    ): array {
        if ($level !== null && $level !== '' && strtolower($level) !== 'all') {
            $level = strtolower($level);
            $entries = array_values(array_filter(
                $entries,
                fn (LogEntry $entry) => $entry->level === $level
            ));
        }

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);

            $entries = array_values(array_filter(
                $entries,
                function (LogEntry $entry) use ($needle) {
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $entry->message,
                        $entry->stackTrace,
                        $entry->context !== null ? json_encode($entry->context) : null,
                    ])));

                    return str_contains($haystack, $needle);
                }
            ));
        }

        if ($from !== null && $from !== '') {
            $fromDate = CarbonImmutable::parse($from);
            $entries = array_values(array_filter(
                $entries,
                fn (LogEntry $entry) => $entry->timestamp !== null && $entry->timestamp->gte($fromDate)
            ));
        }

        if ($to !== null && $to !== '') {
            $toDate = CarbonImmutable::parse($to);
            $entries = array_values(array_filter(
                $entries,
                fn (LogEntry $entry) => $entry->timestamp !== null && $entry->timestamp->lte($toDate)
            ));
        }

        return $entries;
    }

    /**
     * @param  array<int, LogEntry>  $entries
     * @return array<int, LogEntry>
     */
    public function sort(array $entries, string $direction = 'desc'): array
    {
        usort($entries, function (LogEntry $a, LogEntry $b) use ($direction) {
            $aTime = $a->timestamp?->timestamp ?? 0;
            $bTime = $b->timestamp?->timestamp ?? 0;

            return $direction === 'asc' ? $aTime <=> $bTime : $bTime <=> $aTime;
        });

        return $entries;
    }

    /**
     * @param  array<int, LogEntry>  $entries
     * @return array<int, LogEntry>
     */
    public function paginate(array $entries, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        return array_slice($entries, $offset, $perPage);
    }

    /**
     * @param  array<int, LogEntry>  $entries
     * @return array<string, int>
     */
    public function stats(array $entries): array
    {
        $stats = array_fill_keys([
            'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug',
        ], 0);

        foreach ($entries as $entry) {
            $stats[$entry->level] = ($stats[$entry->level] ?? 0) + 1;
        }

        return $stats;
    }
}
