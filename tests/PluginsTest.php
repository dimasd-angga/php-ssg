<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Site;
use PHPUnit\Framework\TestCase;

final class PluginsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/php-ssg-plugins-' . uniqid();
        mkdir($this->root);
        mkdir($this->root . '/content', 0755, true);
        mkdir($this->root . '/templates', 0755, true);
        mkdir($this->root . '/plugins', 0755, true);

        file_put_contents($this->root . '/templates/base.php', '<?= $content ?>');
        file_put_contents($this->root . '/templates/page.php',
            '<?php $this->layout(\'base\') ?>RT:<?= $page->readingTime ?? "n/a" ?>');

        file_put_contents($this->root . '/content/index.md',
            "---\ntitle: Home\n---\n" . str_repeat("word ", 600));

        file_put_contents($this->root . '/plugins/rt.php',
            '<?php use PhpSsg\Page; return ["onPage" => function (Page $p) { $p->readingTime = (int) ceil(str_word_count(strip_tags($p->rawContent)) / 200); return $p; }];');
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

    public function testOnPageHookMutatesPage(): void
    {
        $config = [
            'site' => ['name' => 'T'],
            'build' => ['output' => 'dist'],
            'plugins' => ['plugins/rt.php'],
        ];
        (new Site($this->root, $config))->build();
        $html = file_get_contents($this->root . '/dist/index.html');
        $this->assertMatchesRegularExpression('/RT:[1-9]\d*/', $html);
    }

    public function testNoPluginsStillBuilds(): void
    {
        $config = ['site' => ['name' => 'T'], 'build' => ['output' => 'dist']];
        (new Site($this->root, $config))->build();
        $html = file_get_contents($this->root . '/dist/index.html');
        $this->assertStringContainsString('RT:n/a', $html);
    }
}
