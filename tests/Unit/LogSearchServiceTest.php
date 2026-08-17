<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Unit;

use Carbon\CarbonImmutable;
use Luiscamp\LaravelLogMonitor\DTO\LogEntry;
use Luiscamp\LaravelLogMonitor\Services\LogSearchService;
use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogSearchServiceTest extends TestCase
{
    private LogSearchService $search;

    /** @var array<int, LogEntry> */
    private array $entries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->search = new LogSearchService;

        $this->entries = [
            new LogEntry(CarbonImmutable::parse('2026-08-17 10:00:00'), 'production', 'error', 'Undefined variable $user'),
            new LogEntry(CarbonImmutable::parse('2026-08-17 11:00:00'), 'production', 'warning', 'Deprecated function used'),
            new LogEntry(CarbonImmutable::parse('2026-08-17 12:00:00'), 'production', 'info', 'UserService started'),
        ];
    }

    public function test_it_filters_by_level(): void
    {
        $result = $this->search->filter($this->entries, level: 'error');

        $this->assertCount(1, $result);
        $this->assertSame('error', $result[0]->level);
    }

    public function test_it_filters_by_text_search_across_message(): void
    {
        $result = $this->search->filter($this->entries, search: 'UserService');

        $this->assertCount(1, $result);
        $this->assertSame('info', $result[0]->level);
    }

    public function test_all_level_returns_everything(): void
    {
        $result = $this->search->filter($this->entries, level: 'all');

        $this->assertCount(3, $result);
    }

    public function test_it_sorts_descending_by_default(): void
    {
        $result = $this->search->sort($this->entries);

        $this->assertSame('info', $result[0]->level);
        $this->assertSame('error', $result[2]->level);
    }

    public function test_it_sorts_ascending(): void
    {
        $result = $this->search->sort($this->entries, 'asc');

        $this->assertSame('error', $result[0]->level);
        $this->assertSame('info', $result[2]->level);
    }

    public function test_it_paginates(): void
    {
        $result = $this->search->paginate($this->entries, page: 2, perPage: 1);

        $this->assertCount(1, $result);
        $this->assertSame('warning', $result[0]->level);
    }

    public function test_it_computes_stats(): void
    {
        $stats = $this->search->stats($this->entries);

        $this->assertSame(1, $stats['error']);
        $this->assertSame(1, $stats['warning']);
        $this->assertSame(1, $stats['info']);
        $this->assertSame(0, $stats['debug']);
    }
}
