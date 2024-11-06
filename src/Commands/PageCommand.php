<?php

declare(strict_types=1);

namespace PhpSsg\Commands;

/**
 * Creates a new blank page under content/ at the given slug.
 */
class PageCommand
{
    public function run(array $args): int
    {
        $slug = $args[0] ?? null;
        if ($slug === null) {
            fwrite(STDERR, "Usage: php-ssg page <slug>\n");
            return 1;
        }
        $slug = trim($slug, '/');
        $path = getcwd() . "/content/{$slug}.md";

        if (file_exists($path)) {
            fwrite(STDERR, "Page already exists: {$path}\n");
            return 1;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $title = ucwords(str_replace(['-', '_', '/'], ' ', $slug));
        $content = <<<MD
---
title: {$title}
layout: page
---

# {$title}

Edit this page.

MD;

        file_put_contents($path, $content);
        echo "Created {$path}\n";
        return 0;
    }
}
