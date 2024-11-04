<?php

declare(strict_types=1);

namespace PhpSsg\Commands;

/**
 * Creates a new blog post under content/blog/ with today's date as filename
 * prefix. The slug is derived from the title (kebab-cased).
 */
class PostCommand
{
    public function run(array $args): int
    {
        $title = trim(implode(' ', $args));
        if ($title === '') {
            fwrite(STDERR, "Usage: php-ssg post <title>\n");
            return 1;
        }

        $date = date('Y-m-d');
        $slug = $this->slugify($title);
        $path = getcwd() . "/content/blog/{$date}-{$slug}.md";

        if (file_exists($path)) {
            fwrite(STDERR, "Post already exists: {$path}\n");
            return 1;
        }

        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $content = <<<MD
---
title: {$title}
date: {$date}
tags: []
layout: post
description:
---

Write your post here.

MD;

        file_put_contents($path, $content);
        echo "Created {$path}\n";
        return 0;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
