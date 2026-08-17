<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Unit;

use Luiscamp\LaravelLogMonitor\Services\LogParserService;
use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogParserTest extends TestCase
{
    private LogParserService $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new LogParserService;
    }

    public function test_it_parses_a_single_line_entry(): void
    {
        $entries = $this->parser->parse(
            '[2026-08-17 12:25:13] production.ERROR: Undefined variable $user'
        );

        $this->assertCount(1, $entries);
        $this->assertSame('production', $entries[0]->environment);
        $this->assertSame('error', $entries[0]->level);
        $this->assertSame('Undefined variable $user', $entries[0]->message);
        $this->assertNotNull($entries[0]->timestamp);
        $this->assertSame('2026-08-17 12:25:13', $entries[0]->timestamp->format('Y-m-d H:i:s'));
    }

    public function test_it_groups_multiline_stack_traces_into_a_single_entry(): void
    {
        $raw = <<<'LOG'
        [2026-08-17 12:25:13] production.ERROR: SQLSTATE[HY000] Connection failed
        Stack trace:
        #0 app/Services/UserService.php(84): PDO->__construct()
        #1 app/Http/Controllers/UserController.php(32): UserService->find()
        #2 {main}
        LOG;

        $entries = $this->parser->parse($raw);

        $this->assertCount(1, $entries);
        $this->assertSame('SQLSTATE[HY000] Connection failed', $entries[0]->message);
        $this->assertStringContainsString('#0 app/Services/UserService.php(84)', $entries[0]->stackTrace);
        $this->assertStringContainsString('#2 {main}', $entries[0]->stackTrace);
    }

    public function test_it_parses_multiple_entries_in_one_file(): void
    {
        $raw = <<<'LOG'
        [2026-08-17 12:00:00] production.INFO: First entry
        [2026-08-17 12:05:00] production.WARNING: Second entry
        with an extra line
        [2026-08-17 12:10:00] production.DEBUG: Third entry
        LOG;

        $entries = $this->parser->parse($raw);

        $this->assertCount(3, $entries);
        $this->assertSame('info', $entries[0]->level);
        $this->assertSame('warning', $entries[1]->level);
        $this->assertStringContainsString('with an extra line', $entries[1]->message);
        $this->assertSame('debug', $entries[2]->level);
    }

    public function test_it_returns_empty_array_for_empty_input(): void
    {
        $this->assertSame([], $this->parser->parse(''));
    }

    public function test_it_ignores_leading_unstructured_content(): void
    {
        $raw = "garbage line before any header\n[2026-08-17 12:00:00] production.INFO: real entry";

        $entries = $this->parser->parse($raw);

        $this->assertCount(1, $entries);
        $this->assertSame('real entry', $entries[0]->message);
    }
}
