<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Splits a list of items into pages. Each Pagination object represents
 * one page: it knows its own slice of items, what number it is, and how
 * to reach the previous and next page URLs.
 *
 * Usage in templates:
 *   <?php foreach ($pagination->items as $post): ?> ... <?php endforeach ?>
 *   <a href="<?= $pagination->prevUrl ?>">Prev</a>
 *   <a href="<?= $pagination->nextUrl ?>">Next</a>
 */
class Pagination
{
    public int $page;
    public int $totalPages;
    public int $perPage;
    public int $total;
    public array $items;
    public ?string $prevUrl;
    public ?string $nextUrl;
    public string $baseUrl;

    public function __construct(int $page, int $totalPages, int $perPage, int $total, array $items, string $baseUrl)
    {
        $this->page = $page;
        $this->totalPages = $totalPages;
        $this->perPage = $perPage;
        $this->total = $total;
        $this->items = $items;
        $this->baseUrl = rtrim($baseUrl, '/') . '/';

        $this->prevUrl = $page > 1
            ? ($page === 2 ? $this->baseUrl : $this->baseUrl . 'page/' . ($page - 1) . '/')
            : null;
        $this->nextUrl = $page < $totalPages
            ? $this->baseUrl . 'page/' . ($page + 1) . '/'
            : null;
    }

    /**
     * @return Pagination[]
     */
    public static function paginate(array $items, int $perPage, string $baseUrl): array
    {
        $total = count($items);

        // Edge case: single page (or zero items) — emit one page with no prev/next.
        // Previously this branch could yield a Pagination with $totalPages=1 but
        // nextUrl populated when $total > 0 and $perPage was 0 (division by zero
        // implicit in ceil()), so handle explicitly here.
        if ($perPage < 1 || $total <= $perPage) {
            return [new self(1, 1, max(1, $perPage), $total, $items, $baseUrl)];
        }

        $totalPages = (int) ceil($total / $perPage);
        $pages = [];
        for ($i = 0; $i < $totalPages; $i++) {
            $slice = array_slice($items, $i * $perPage, $perPage);
            $pages[] = new self($i + 1, $totalPages, $perPage, $total, $slice, $baseUrl);
        }
        return $pages;
    }
}
