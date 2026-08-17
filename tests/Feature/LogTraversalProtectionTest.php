<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Tests\Feature;

use Luiscamp\LaravelLogMonitor\Tests\TestCase;

final class LogTraversalProtectionTest extends TestCase
{
    public function test_it_rejects_relative_traversal_in_show_endpoint(): void
    {
        $response = $this->getJson('/system/logs/..%2F..%2Fetc%2Fpasswd');

        $response->assertNotFound();
    }

    public function test_it_rejects_encoded_absolute_paths(): void
    {
        $response = $this->getJson('/system/logs/'.rawurlencode('/etc/passwd'));

        $response->assertNotFound();
    }

    public function test_it_rejects_nonexistent_file(): void
    {
        $response = $this->getJson('/system/logs/does-not-exist.log');

        $response->assertNotFound();
    }

    public function test_it_rejects_disallowed_extension(): void
    {
        $this->putLog('secret.php', '<?php echo "no";');

        $response = $this->getJson('/system/logs/secret.php');

        $response->assertNotFound();
    }

    public function test_it_rejects_traversal_on_download(): void
    {
        $response = $this->get('/system/logs/..%2F..%2Fetc%2Fpasswd/download');

        $response->assertNotFound();
    }

    public function test_it_serves_a_legitimately_named_file(): void
    {
        $this->putLog('laravel.log', "[2026-08-17 12:00:00] production.INFO: ok\n");

        $response = $this->getJson('/system/logs/laravel.log');

        $response->assertOk();
        $response->assertJsonPath('file.name', 'laravel.log');
    }

    public function test_it_handles_an_empty_log_file(): void
    {
        $this->putLog('empty.log', '');

        $response = $this->getJson('/system/logs/empty.log');

        $response->assertOk();
        $response->assertJsonPath('total', 0);
    }
}
