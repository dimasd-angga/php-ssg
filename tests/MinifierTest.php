<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Minifier;
use PHPUnit\Framework\TestCase;

final class MinifierTest extends TestCase
{
    private Minifier $m;

    protected function setUp(): void
    {
        $this->m = new Minifier();
    }

    public function testCollapsesBetweenTags(): void
    {
        $out = $this->m->minify("<div>\n  <p>hi</p>\n</div>");
        $this->assertSame('<div><p>hi</p></div>', $out);
    }

    public function testPreservesPreBlocks(): void
    {
        $in = "<div>\n  <pre>  one\n    two\n  three  </pre>\n</div>";
        $out = $this->m->minify($in);
        $this->assertStringContainsString("<pre>  one\n    two\n  three  </pre>", $out);
    }

    public function testPreservesCodeBlocks(): void
    {
        $in = "<p>x</p>\n<pre><code class=\"language-php\">function f() {\n    return 1;\n}\n</code></pre>\n<p>y</p>";
        $out = $this->m->minify($in);
        $this->assertStringContainsString("function f() {\n    return 1;\n}", $out);
    }

    public function testStripsComments(): void
    {
        $out = $this->m->minify('<div><!-- gone --><p>hi</p></div>');
        $this->assertSame('<div><p>hi</p></div>', $out);
    }

    public function testKeepsIeConditionalComments(): void
    {
        $in = '<head><!--[if IE]><script src="ie.js"></script><![endif]--></head>';
        $out = $this->m->minify($in);
        $this->assertStringContainsString('<!--[if IE]>', $out);
    }
}
