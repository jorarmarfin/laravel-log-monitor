<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Services;

use Carbon\CarbonImmutable;
use Luiscamp\LaravelLogMonitor\Contracts\LogParserInterface;
use Luiscamp\LaravelLogMonitor\DTO\LogEntry;

/**
 * Parses Laravel/Monolog formatted log text into structured LogEntry
 * objects, correctly grouping multiline messages and stack traces into
 * the single entry they belong to.
 */
final class LogParserService implements LogParserInterface
{
    private const HEADER_PATTERN = '/^\[(?<timestamp>\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?)\]\s+(?<environment>[\w\-]+)\.(?<level>[A-Z]+):\s*(?<message>.*)$/';

    public function parse(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        $entries = [];
        $current = null;
        $bodyLines = [];

        $flush = function () use (&$entries, &$current, &$bodyLines): void {
            if ($current === null) {
                return;
            }

            $entries[] = $this->buildEntry($current, $bodyLines);
            $current = null;
            $bodyLines = [];
        };

        foreach ($lines as $line) {
            if (preg_match(self::HEADER_PATTERN, $line, $matches) === 1) {
                $flush();
                $current = $matches;
                $bodyLines = [];

                continue;
            }

            if ($current === null) {
                // Content before the first recognizable header; ignore.
                continue;
            }

            $bodyLines[] = $line;
        }

        $flush();

        return $entries;
    }

    /**
     * @param  array<string, string>  $header
     * @param  array<int, string>  $bodyLines
     */
    private function buildEntry(array $header, array $bodyLines): LogEntry
    {
        $timestamp = null;

        try {
            $timestamp = CarbonImmutable::parse($header['timestamp']);
        } catch (\Throwable) {
            $timestamp = null;
        }

        $message = $header['message'];
        $stackTrace = null;
        $context = null;

        $body = trim(implode("\n", $bodyLines));

        if ($body !== '') {
            $traceOffset = mb_strpos($body, 'Stack trace:');

            if ($traceOffset !== false) {
                $extraMessage = trim(mb_substr($body, 0, $traceOffset));
                $stackTrace = trim(mb_substr($body, $traceOffset + mb_strlen('Stack trace:')));

                if ($extraMessage !== '') {
                    $message .= "\n".$extraMessage;
                }
            } else {
                $message .= "\n".$body;
            }
        }

        [$message, $context] = $this->extractTrailingJsonContext($message);

        return new LogEntry(
            timestamp: $timestamp,
            environment: $header['environment'] !== '' ? $header['environment'] : null,
            level: strtolower($header['level']),
            message: trim($message),
            context: $context,
            stackTrace: $stackTrace,
        );
    }

    /**
     * Laravel sometimes appends a JSON context/extra blob at the end of
     * the message line, e.g. `Something failed {"user_id":42}`.
     *
     * @return array{0: string, 1: array<string, mixed>|null}
     */
    private function extractTrailingJsonContext(string $message): array
    {
        $jsonStart = mb_strpos($message, '{');

        if ($jsonStart === false) {
            return [$message, null];
        }

        $candidate = mb_substr($message, $jsonStart);

        $decoded = json_decode($candidate, true);

        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return [$message, null];
        }

        return [trim(mb_substr($message, 0, $jsonStart)), $decoded];
    }
}
