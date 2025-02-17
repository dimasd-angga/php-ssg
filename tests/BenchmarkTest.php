<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Site;
use PHPUnit\Framework\TestCase;

/**
 * Build-time benchmarks. Not run as part of the default suite (they create
 * thousands of files). Run explicitly:
 *   vendor/bin/phpunit --filter BenchmarkTest --group=bench
 *
 * @group bench
 */
final class BenchmarkTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/php-ssg-bench-' . uniqid();
        mkdir($this->root . '/content/blog', 0755, true);
        mkdir($this->root . '/templates', 0755, true);
        file_put_contents($this->root . '/templates/base.php', '<?= $content ?>');
        file_put_contents($this->root . '/templates/page.php',
            '<?php $this->layout(\'base\') ?><?= $page->content ?>');
        file_put_contents($this->root . '/templates/post.php',
            '<?php $this->layout(\'base\') ?><h1><?= $page->title ?></h1><?= $page->content ?>');
        file_put_contents($this->root . '/content/index.md',
            "---\ntitle: Home\n---\n# Home");
    }

    protected function tearDown(): void
    {
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($this->root);
    }

    /** @group bench */
    public function testBuildScalesToHundreds(): void
    {
        $this->seedPosts(100);
        $config = $this->config(false);

        $start = microtime(true);
        (new Site($this->root, $config))->build();
        $elapsed = (microtime(true) - $start) * 1000;

        fwrite(STDOUT, sprintf("\n  100 pages: %.0fms\n", $elapsed));
        $this->assertLessThan(5000, $elapsed, "100-page build should complete under 5s");
    }

    private function seedPosts(int $n): void
    {
        for ($i = 1; $i <= $n; $i++) {
            $date = (new \DateTimeImmutable("2025-01-01"))->modify("+{$i} days")->format('Y-m-d');
            file_put_contents(
                $this->root . "/content/blog/{$date}-post-{$i}.md",
                "---\ntitle: Post {$i}\ndate: {$date}\ntags: [bench]\n---\n# Post {$i}\n\nLorem ipsum body content for post number {$i}.\n"
            );
        }
    }

    private function config(bool $parallel): array
    {
        return [
            'site' => ['name' => 'B'],
            'build' => ['output' => 'dist', 'parallel' => $parallel],
            'collections' => ['blog' => ['path' => 'content/blog', 'layout' => 'post']],
        ];
    }
}
