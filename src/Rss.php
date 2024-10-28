<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Generates an RSS 2.0 feed for a collection.
 *
 * Outputs one <item> per post with title, link, pubDate, description
 * (frontmatter description, falling back to a content excerpt), and
 * the full rendered HTML wrapped in CDATA in the <content:encoded> field
 * for feed readers that support it.
 */
class Rss
{
    /**
     * @param Page[] $posts
     */
    public function generate(array $posts, array $site, string $collectionUrl, string $collectionTitle): string
    {
        $siteUrl = rtrim($site['url'] ?? '', '/');
        $siteName = $site['name'] ?? 'Site';
        $feedUrl = $siteUrl . rtrim($collectionUrl, '/') . '/feed.xml';

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<rss version=\"2.0\" xmlns:content=\"http://purl.org/rss/1.0/modules/content/\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
        $xml .= "  <channel>\n";
        $xml .= "    <title>" . htmlspecialchars($siteName . ' - ' . $collectionTitle, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</title>\n";
        $xml .= "    <link>" . htmlspecialchars($siteUrl . $collectionUrl, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</link>\n";
        $xml .= "    <description>" . htmlspecialchars($site['description'] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8') . "</description>\n";
        $xml .= "    <atom:link href=\"" . htmlspecialchars($feedUrl, ENT_QUOTES | ENT_XML1, 'UTF-8') . "\" rel=\"self\" type=\"application/rss+xml\" />\n";
        $xml .= "    <language>" . htmlspecialchars($site['locale'] ?? 'en', ENT_QUOTES | ENT_XML1, 'UTF-8') . "</language>\n";

        foreach ($posts as $post) {
            $url = $siteUrl . $post->url;
            $pubDate = $post->date instanceof \DateTimeImmutable
                ? $post->date->format(\DATE_RSS)
                : (new \DateTimeImmutable())->format(\DATE_RSS);
            $description = $post->description ?? $post->excerpt ?? strip_tags(mb_substr($post->content, 0, 280));

            $xml .= "    <item>\n";
            $xml .= "      <title>" . htmlspecialchars($post->title, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</title>\n";
            $xml .= "      <link>" . htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</link>\n";
            $xml .= "      <guid isPermaLink=\"true\">" . htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</guid>\n";
            $xml .= "      <pubDate>" . $pubDate . "</pubDate>\n";
            $xml .= "      <description>" . htmlspecialchars($description, ENT_QUOTES | ENT_XML1, 'UTF-8') . "</description>\n";
            $xml .= "      <content:encoded><![CDATA[" . $post->content . "]]></content:encoded>\n";
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n</rss>\n";
        return $xml;
    }
}
