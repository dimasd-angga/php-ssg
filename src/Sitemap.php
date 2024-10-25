<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Generates a sitemap.xml file from a list of Page objects.
 *
 * Implements the basic sitemaps.org 0.9 schema. Pages with a $date are
 * emitted with <lastmod>; pages without one omit the element.
 */
class Sitemap
{
    /**
     * @param Page[] $pages
     */
    public function generate(array $pages, string $siteUrl): string
    {
        $base = rtrim($siteUrl, '/');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($pages as $page) {
            $loc = $base . $page->url;
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($loc, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</loc>\n";
            if ($page->date instanceof \DateTimeImmutable) {
                $xml .= "    <lastmod>" . $page->date->format('Y-m-d') . "</lastmod>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";
        return $xml;
    }
}
