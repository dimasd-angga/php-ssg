<?php

/**
 * Reading-time plugin example.
 *
 * Adds a `readingTime` integer (in minutes) to every page based on its
 * raw Markdown word count, assuming an average reading speed of 200 wpm.
 *
 * Templates can use it like:
 *   <span><?= $page->readingTime ?> min read</span>
 *
 * To enable, add this file to your ssg.config.php:
 *   'plugins' => ['examples/plugins/reading-time.php'],
 */

use PhpSsg\Page;

return [
    'onPage' => function (Page $page): Page {
        $words = str_word_count(strip_tags($page->rawContent));
        $page->readingTime = max(1, (int) ceil($words / 200));
        return $page;
    },
];
