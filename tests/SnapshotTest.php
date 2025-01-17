<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Site;
use PHPUnit\Framework\TestCase;

/**
 * Snapshot of a full build: scaffolds a deterministic site fixture,
 * builds it, and verifies the dist/ contents match expected shape.
 */
final class SnapshotTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/php-ssg-snap-' . uniqid();
        mkdir($this->root . '/content/blog', 0755, true);
        mkdir($this->root . '/templates', 0755, true);
        mkdir($this->root . '/assets/css', 0755, true);

        file_put_contents($this->root . '/templates/base.php',
            '<html><head><title><?= $title ?? "x" ?></title>' .
            '<link rel="stylesheet" href="<?= $this->asset(\'css/main.css\') ?>"></head>' .
            '<body><?= $content ?></body></html>');
        file_put_contents($this->root . '/templates/page.php',
            '<?php $this->layout(\'base\', [\'title\' => $page->title]) ?><?= $page->content ?>');
        file_put_contents($this->root . '/templates/post.php',
            '<?php $this->layout(\'base\', [\'title\' => $page->title]) ?>' .
            '<h1><?= $page->title ?></h1><?= $page->content ?>');

        file_put_contents($this->root . '/content/index.md',
            "---\ntitle: Home\n---\n# Welcome");
        file_put_contents($this->root . '/content/blog/2025-01-15-first.md',
            "---\ntitle: First\ndate: 2025-01-15\ntags: [intro]\n---\nHello world.");

        file_put_contents($this->root . '/assets/css/main.css', 'body{color:#111}');
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

    public function testProducesExpectedFiles(): void
    {
        $config = [
            'site' => ['name' => 'Snap', 'url' => 'https://snap.test'],
            'build' => ['output' => 'dist'],
            'collections' => ['blog' => ['path' => 'content/blog', 'layout' => 'post', 'rss' => true]],
        ];
        (new Site($this->root, $config))->build();

        $this->assertFileExists($this->root . '/dist/index.html');
        $this->assertFileExists($this->root . '/dist/blog/first/index.html');
        $this->assertFileExists($this->root . '/dist/sitemap.xml');
        $this->assertFileExists($this->root . '/dist/blog/feed.xml');

        $assetFiles = glob($this->root . '/dist/assets/css/main.*.css');
        $this->assertCount(1, $assetFiles, 'expected fingerprinted main.css');

        $homeHtml = file_get_contents($this->root . '/dist/index.html');
        $this->assertStringContainsString('<title>Home</title>', $homeHtml);
        $this->assertMatchesRegularExpression('#/assets/css/main\.[a-f0-9]{8}\.css#', $homeHtml);

        $sitemap = file_get_contents($this->root . '/dist/sitemap.xml');
        $this->assertStringContainsString('https://snap.test/', $sitemap);
        $this->assertStringContainsString('https://snap.test/blog/first/', $sitemap);

        $feed = file_get_contents($this->root . '/dist/blog/feed.xml');
        $this->assertStringContainsString('<title>Snap - Blog</title>', $feed);
        $this->assertStringContainsString('First', $feed);
    }
}
