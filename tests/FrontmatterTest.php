<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Frontmatter;
use PHPUnit\Framework\TestCase;

final class FrontmatterTest extends TestCase
{
    private Frontmatter $fm;

    protected function setUp(): void
    {
        $this->fm = new Frontmatter();
    }

    public function testParsesSimpleFrontmatter(): void
    {
        $raw = "---\ntitle: Hello\ndate: 2024-09-11\n---\nBody here.";
        $result = $this->fm->parse($raw);
        $this->assertSame('Hello', $result['data']['title']);
        $this->assertSame('2024-09-11', $result['data']['date']);
        $this->assertSame('Body here.', $result['content']);
    }

    public function testParsesFlowListTags(): void
    {
        $raw = "---\ntags: [php, web, tutorial]\n---\n";
        $result = $this->fm->parse($raw);
        $this->assertSame(['php', 'web', 'tutorial'], $result['data']['tags']);
    }

    public function testParsesBlockList(): void
    {
        $raw = "---\ntags:\n  - php\n  - blog\n---\n";
        $result = $this->fm->parse($raw);
        $this->assertSame(['php', 'blog'], $result['data']['tags']);
    }

    public function testParsesBooleansAndNumbers(): void
    {
        $raw = "---\ndraft: true\nviews: 42\nratio: 1.5\nempty: null\n---\n";
        $result = $this->fm->parse($raw);
        $this->assertTrue($result['data']['draft']);
        $this->assertSame(42, $result['data']['views']);
        $this->assertSame(1.5, $result['data']['ratio']);
        $this->assertNull($result['data']['empty']);
    }

    public function testParsesQuotedStrings(): void
    {
        $raw = "---\ntitle: \"Hello: with colon\"\n---\n";
        $result = $this->fm->parse($raw);
        $this->assertSame('Hello: with colon', $result['data']['title']);
    }

    public function testNoFrontmatterReturnsEmptyData(): void
    {
        $raw = "# Just a heading\n\nNo frontmatter.";
        $result = $this->fm->parse($raw);
        $this->assertSame([], $result['data']);
        $this->assertSame($raw, $result['content']);
    }
}
