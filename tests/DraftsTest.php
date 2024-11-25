<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Site;
use PHPUnit\Framework\TestCase;

final class DraftsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/php-ssg-drafts-' . uniqid();
        mkdir($this->root);
        mkdir($this->root . '/content/blog', 0755, true);
        mkdir($this->root . '/templates', 0755, true);

        file_put_contents($this->root . '/templates/base.php',
            '<?= $content ?>');
        file_put_contents($this->root . '/templates/page.php',
            '<?php $this->layout(\'base\') ?><?= $page->content ?>');
        file_put_contents($this->root . '/templates/post.php',
            '<?php $this->layout(\'base\') ?>POST <?= $page->title ?>');

        file_put_contents($this->root . '/content/blog/2024-01-01-published.md',
            "---\ntitle: Published\ndate: 2024-01-01\n---\nbody");
        file_put_contents($this->root . '/content/blog/2024-01-02-draft.md',
            "---\ntitle: Draft\ndate: 2024-01-02\ndraft: true\n---\nbody");
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->root);
    }

    public function testDraftsExcludedByDefault(): void
    {
        $site = new Site($this->root, $this->config(false));
        $site->build();
        $this->assertFileExists($this->root . '/dist/blog/published/index.html');
        $this->assertFileDoesNotExist($this->root . '/dist/blog/draft/index.html');
    }

    public function testDraftsIncludedWithFlag(): void
    {
        $site = new Site($this->root, $this->config(true));
        $site->build();
        $this->assertFileExists($this->root . '/dist/blog/published/index.html');
        $this->assertFileExists($this->root . '/dist/blog/draft/index.html');
    }

    private function config(bool $drafts): array
    {
        return [
            'site' => ['name' => 'T'],
            'build' => ['output' => 'dist', 'drafts' => $drafts],
            'collections' => ['blog' => ['path' => 'content/blog', 'layout' => 'post']],
        ];
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
