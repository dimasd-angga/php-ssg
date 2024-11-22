<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PHPUnit\Framework\TestCase;

/**
 * End-to-end test: scaffold a site with `php-ssg new`, build it, and
 * verify the output HTML contains the expected pieces.
 */
final class CliIntegrationTest extends TestCase
{
    private string $workdir;
    private string $bin;

    protected function setUp(): void
    {
        $this->workdir = sys_get_temp_dir() . '/php-ssg-int-' . uniqid();
        mkdir($this->workdir);
        $this->bin = realpath(__DIR__ . '/../bin/php-ssg');
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->workdir);
    }

    public function testScaffoldAndBuild(): void
    {
        chdir($this->workdir);

        $out = shell_exec("php " . escapeshellarg($this->bin) . " new mysite 2>&1");
        $this->assertStringContainsString('Created mysite/', $out);
        $this->assertFileExists($this->workdir . '/mysite/ssg.config.php');
        $this->assertFileExists($this->workdir . '/mysite/content/index.md');

        chdir($this->workdir . '/mysite');
        $build = shell_exec("php " . escapeshellarg($this->bin) . " build 2>&1");
        $this->assertStringContainsString('Built', $build);
        $this->assertFileExists($this->workdir . '/mysite/dist/index.html');
        $this->assertFileExists($this->workdir . '/mysite/dist/blog/welcome/index.html');

        $html = file_get_contents($this->workdir . '/mysite/dist/index.html');
        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('Mysite', $html);

        $blogHtml = file_get_contents($this->workdir . '/mysite/dist/blog/welcome/index.html');
        $this->assertStringContainsString('Welcome to php-ssg', $blogHtml);
    }

    public function testValidate(): void
    {
        chdir($this->workdir);
        shell_exec("php " . escapeshellarg($this->bin) . " new validsite 2>&1");
        chdir($this->workdir . '/validsite');
        $out = shell_exec("php " . escapeshellarg($this->bin) . " validate 2>&1");
        $this->assertStringContainsString('Validation passed', $out);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }
}
