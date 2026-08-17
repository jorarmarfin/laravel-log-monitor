<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\DTO;

use Carbon\CarbonImmutable;

final readonly class LogFile
{
    public function __construct(
        public string $identifier,
        public string $name,
        public string $path,
        public int $size,
        public CarbonImmutable $modifiedAt,
    ) {}

    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = (float) $this->size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'size' => $this->size,
            'human_size' => $this->humanSize(),
            'modified_at' => $this->modifiedAt->toIso8601String(),
        ];
    }
}
