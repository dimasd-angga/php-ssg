<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Template;
use PHPUnit\Framework\TestCase;

final class TemplateTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/php-ssg-tpl-' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
    }

    public function testRendersSimpleTemplate(): void
    {
        file_put_contents($this->dir . '/hello.php', '<p>Hi <?= $this->e($name) ?></p>');
        $tpl = new Template($this->dir);
        $this->assertSame('<p>Hi Dimas</p>', $tpl->render('hello', ['name' => 'Dimas']));
        $this->assertStringContainsString('Hi &lt;strong&gt;', $tpl->render('hello', ['name' => '<strong>x</strong>']));
    }

    public function testLayoutInheritance(): void
    {
        file_put_contents($this->dir . '/base.php',
            '<html><head><title><?= $this->e($title) ?></title></head><body><?= $content ?></body></html>');
        file_put_contents($this->dir . '/page.php',
            "<?php \$this->layout('base', ['title' => \$title]) ?><h1><?= \$this->e(\$title) ?></h1>");

        $tpl = new Template($this->dir);
        $out = $tpl->render('page', ['title' => 'Hello']);

        $this->assertStringContainsString('<title>Hello</title>', $out);
        $this->assertStringContainsString('<h1>Hello</h1>', $out);
        $this->assertStringStartsWith('<html>', $out);
    }
}
