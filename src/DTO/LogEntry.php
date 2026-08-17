<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\DTO;

use Carbon\CarbonImmutable;

final readonly class LogEntry
{
    /**
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        public ?CarbonImmutable $timestamp,
        public ?string $environment,
        public string $level,
        public string $message,
        public ?array $context = null,
        public ?string $stackTrace = null,
    ) {}

    public function levelLabel(): string
    {
        return strtoupper($this->level);
    }

    /**
     * Plain-text representation suitable for copying (e.g. into AI tools).
     */
    public function toCopyableText(): string
    {
        $lines = [];

        $lines[] = $this->timestamp !== null
            ? '['.$this->timestamp->format('Y-m-d H:i:s').']'
            : '[unknown time]';

        $lines[] = trim(($this->environment !== null ? $this->environment.'.' : '').$this->levelLabel());
        $lines[] = '';
        $lines[] = $this->message;

        if ($this->stackTrace !== null && $this->stackTrace !== '') {
            $lines[] = '';
            $lines[] = 'Stack trace:';
            $lines[] = $this->stackTrace;
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp?->toIso8601String(),
            'environment' => $this->environment,
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
            'stack_trace' => $this->stackTrace,
        ];
    }
}
