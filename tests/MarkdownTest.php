<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Markdown;
use PHPUnit\Framework\TestCase;

final class MarkdownTest extends TestCase
{
    private Markdown $md;

    protected function setUp(): void
    {
        $this->md = new Markdown();
    }

    public function testHeadings(): void
    {
        $this->assertSame('<h1>Hello</h1>', $this->md->toHtml('# Hello'));
        $this->assertSame('<h3>Sub</h3>', $this->md->toHtml('### Sub'));
    }

    public function testParagraphs(): void
    {
        $this->assertSame('<p>A line.</p>', $this->md->toHtml('A line.'));
    }

    public function testInlineFormatting(): void
    {
        $out = $this->md->toHtml('**bold** and *italic* and `code`.');
        $this->assertStringContainsString('<strong>bold</strong>', $out);
        $this->assertStringContainsString('<em>italic</em>', $out);
        $this->assertStringContainsString('<code>code</code>', $out);
    }

    public function testLinks(): void
    {
        $out = $this->md->toHtml('Visit [home](/index).');
        $this->assertStringContainsString('<a href="/index">home</a>', $out);
    }

    public function testCodeFenceWithLanguage(): void
    {
        $out = $this->md->toHtml("```php\n<?php echo 'hi';\n```");
        $this->assertStringContainsString('<pre><code class="language-php">', $out);
        $this->assertStringContainsString('&lt;?php', $out);
    }

    public function testUnorderedList(): void
    {
        $out = $this->md->toHtml("- one\n- two\n- three");
        $this->assertStringContainsString('<ul>', $out);
        $this->assertSame(3, substr_count($out, '<li>'));
    }

    public function testHorizontalRule(): void
    {
        $this->assertSame('<hr>', $this->md->toHtml('---'));
    }
}
