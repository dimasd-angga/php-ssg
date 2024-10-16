<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testSinglePageWhenItemsFit(): void
    {
        $pages = Pagination::paginate(range(1, 5), 10, '/blog/');
        $this->assertCount(1, $pages);
        $this->assertSame(1, $pages[0]->totalPages);
        $this->assertNull($pages[0]->prevUrl);
        $this->assertNull($pages[0]->nextUrl);
    }

    public function testSplitsAcrossPages(): void
    {
        $pages = Pagination::paginate(range(1, 25), 10, '/blog/');
        $this->assertCount(3, $pages);
        $this->assertSame(10, count($pages[0]->items));
        $this->assertSame(10, count($pages[1]->items));
        $this->assertSame(5, count($pages[2]->items));
    }

    public function testPrevNextUrls(): void
    {
        $pages = Pagination::paginate(range(1, 25), 10, '/blog/');
        $this->assertNull($pages[0]->prevUrl);
        $this->assertSame('/blog/page/2/', $pages[0]->nextUrl);

        $this->assertSame('/blog/', $pages[1]->prevUrl);
        $this->assertSame('/blog/page/3/', $pages[1]->nextUrl);

        $this->assertSame('/blog/page/2/', $pages[2]->prevUrl);
        $this->assertNull($pages[2]->nextUrl);
    }

    public function testEmptyCollection(): void
    {
        $pages = Pagination::paginate([], 10, '/blog/');
        $this->assertCount(1, $pages);
        $this->assertSame(0, $pages[0]->total);
    }
}
