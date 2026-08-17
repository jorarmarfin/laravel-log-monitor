<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Unit;

use Luiscamp\LaravelLogMonitor\Exceptions\InvalidLogFileException;
use Luiscamp\LaravelLogMonitor\Services\LogFileService;
use Luiscamp\LaravelLogMonitor\Support\LogPathResolver;
use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogFileServiceTest extends TestCase
{
    private function makeService(): LogFileService
    {
        $resolver = new LogPathResolver($this->logsPath(), ['log']);

        return new LogFileService($this->logsPath(), ['log'], $resolver);
    }

    public function test_it_lists_only_allowed_extensions(): void
    {
        $this->putLog('laravel.log', 'content');
        $this->putLog('notes.txt', 'ignored');

        $files = $this->makeService()->all();

        $names = array_map(fn ($f) => $f->name, $files);

        $this->assertContains('laravel.log', $names);
        $this->assertNotContains('notes.txt', $names);
    }

    public function test_default_prefers_laravel_log_when_present(): void
    {
        $this->putLog('worker.log', 'content');
        sleep(0);
        $this->putLog('laravel.log', 'content');

        $default = $this->makeService()->default();

        $this->assertSame('laravel.log', $default->name);
    }

    public function test_default_falls_back_to_most_recently_modified(): void
    {
        $this->putLog('queue.log', 'content');
        touch($this->logsPath().'/queue.log', time() - 100);

        $this->putLog('horizon.log', 'content');
        touch($this->logsPath().'/horizon.log', time());

        $default = $this->makeService()->default();

        $this->assertSame('horizon.log', $default->name);
    }

    public function test_find_rejects_directory_traversal(): void
    {
        $this->expectException(InvalidLogFileException::class);

        $this->makeService()->find('../../etc/passwd');
    }

    public function test_find_rejects_absolute_paths(): void
    {
        $this->expectException(InvalidLogFileException::class);

        $this->makeService()->find('/etc/passwd');
    }

    public function test_find_rejects_disallowed_extensions(): void
    {
        $this->putLog('config.php', '<?php // not a log');

        $this->expectException(InvalidLogFileException::class);

        $this->makeService()->find('config.php');
    }

    public function test_find_rejects_nonexistent_file(): void
    {
        $this->expectException(InvalidLogFileException::class);

        $this->makeService()->find('does-not-exist.log');
    }

    public function test_find_resolves_a_valid_file(): void
    {
        $this->putLog('laravel.log', 'hello');

        $file = $this->makeService()->find('laravel.log');

        $this->assertSame('laravel.log', $file->name);
        $this->assertSame(5, $file->size);
    }
}
