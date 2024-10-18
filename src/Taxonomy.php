<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Builds tag (and other future taxonomy) indexes across a set of pages.
 *
 * Given a list of Page objects, produces a map of tag => Page[] so the
 * Site can generate archive pages (e.g. /tags/php/) without each template
 * recomputing the same buckets.
 */
class Taxonomy
{
    /**
     * @param Page[] $pages
     * @return array<string, Page[]>
     */
    public function tagIndex(array $pages): array
    {
        $index = [];
        foreach ($pages as $page) {
            foreach ($page->tags ?? [] as $tag) {
                $tag = (string) $tag;
                $index[$tag] ??= [];
                $index[$tag][] = $page;
            }
        }
        ksort($index);
        return $index;
    }

    public function slugify(string $tag): string
    {
        $tag = strtolower(trim($tag));
        $tag = preg_replace('/[^a-z0-9]+/', '-', $tag);
        return trim($tag, '-');
    }
}
