<?php

declare(strict_types=1);

namespace PhpSsg\Commands;

/**
 * Scaffolds a new php-ssg site:
 *   content/index.md, content/blog/welcome.md
 *   templates/base.php, page.php, post.php
 *   assets/css/main.css
 *   ssg.config.php
 *
 * Refuses to overwrite an existing non-empty directory.
 */
class NewCommand
{
    public function run(array $args): int
    {
        $name = $args[0] ?? null;
        if ($name === null) {
            fwrite(STDERR, "Usage: php-ssg new <site-name>\n");
            return 1;
        }

        $target = getcwd() . '/' . $name;
        if (is_dir($target) && glob($target . '/*') !== []) {
            fwrite(STDERR, "Directory {$name} already exists and is not empty.\n");
            return 1;
        }

        $this->scaffold($target, $name);
        echo "Created {$name}/\n";
        echo "  cd {$name}\n";
        echo "  php-ssg serve\n";
        return 0;
    }

    private function scaffold(string $root, string $name): void
    {
        $this->writeFile("{$root}/ssg.config.php", $this->configPhp($name));
        $this->writeFile("{$root}/content/index.md", $this->indexMd($name));
        $this->writeFile("{$root}/content/blog/2024-01-01-welcome.md", $this->welcomeMd());
        $this->writeFile("{$root}/templates/base.php", $this->baseTpl());
        $this->writeFile("{$root}/templates/page.php", $this->pageTpl());
        $this->writeFile("{$root}/templates/post.php", $this->postTpl());
        $this->writeFile("{$root}/templates/blog.php", $this->blogTpl());
        $this->writeFile("{$root}/assets/css/main.css", $this->css());
        $this->writeFile("{$root}/.gitignore", "/dist/\n");
    }

    private function writeFile(string $path, string $contents): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($path, $contents);
    }

    private function configPhp(string $name): string
    {
        $title = ucwords(str_replace(['-', '_'], ' ', $name));
        return <<<PHP
<?php

return [
    'site' => [
        'name'   => '{$title}',
        'url'    => 'https://example.com',
        'locale' => 'en',
        'author' => 'Your Name',
    ],
    'build' => [
        'output'    => 'dist',
        'baseUrl'   => '/',
        'minify'    => false,
        'drafts'    => false,
    ],
    'collections' => [
        'blog' => [
            'path'    => 'content/blog',
            'layout'  => 'post',
            'sortBy'  => 'date',
            'perPage' => 10,
            'rss'     => true,
        ],
    ],
    'plugins' => [],
];

PHP;
    }

    private function indexMd(string $name): string
    {
        $title = ucwords(str_replace(['-', '_'], ' ', $name));
        return <<<MD
---
title: {$title}
layout: page
---

# {$title}

Welcome to your new php-ssg site. Edit `content/index.md` to change this page.

Check out the [blog](/blog/2024-01-01-welcome/) for an example post.

MD;
    }

    private function welcomeMd(): string
    {
        return <<<MD
---
title: Welcome to php-ssg
date: 2024-01-01
tags: [meta, intro]
layout: post
description: Your first blog post, powered by php-ssg.
---

# Welcome to php-ssg

This is your first blog post. Files in `content/blog/` are automatically picked
up as part of the `blog` collection.

## What you can do

- **Write Markdown** — full support for headings, lists, code blocks, and links.
- **Add frontmatter** — set title, date, tags, layout, description.
- **Customize templates** — they're plain PHP, edit them in `templates/`.

Run `php-ssg build` to generate the production site in `dist/`.

MD;
    }

    private function baseTpl(): string
    {
        return <<<'PHP'
<!doctype html>
<html lang="<?= $this->e($site->locale ?? 'en') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->e($title ?? ($site->name ?? 'php-ssg')) ?></title>
    <?php if (!empty($description)): ?>
    <meta name="description" content="<?= $this->e($description) ?>">
    <?php endif ?>
    <link rel="stylesheet" href="<?= $this->asset('css/main.css') ?>">
</head>
<body>
    <header class="site-header">
        <a href="/" class="site-title"><?= $this->e($site->name ?? 'Site') ?></a>
        <nav><a href="/blog/">Blog</a></nav>
    </header>
    <main>
        <?= $content ?>
    </main>
    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> <?= $this->e($site->author ?? '') ?> &middot; Built with php-ssg.</p>
    </footer>
</body>
</html>
PHP;
    }

    private function pageTpl(): string
    {
        return <<<'PHP'
<?php $this->layout('base', ['title' => $page->title, 'description' => $page->description ?? null]) ?>
<article class="page">
    <?= $page->content ?>
</article>
PHP;
    }

    private function postTpl(): string
    {
        return <<<'PHP'
<?php $this->layout('base', ['title' => $page->title, 'description' => $page->description ?? null]) ?>
<article class="post">
    <header>
        <h1><?= $this->e($page->title) ?></h1>
        <?php if ($page->date): ?>
        <time datetime="<?= $page->date->format('Y-m-d') ?>"><?= $page->date->format('F j, Y') ?></time>
        <?php endif ?>
        <?php if (!empty($page->tags)): ?>
        <ul class="tags">
            <?php foreach ($page->tags as $tag): ?>
            <li><a href="/tags/<?= $this->e($tag) ?>/"><?= $this->e($tag) ?></a></li>
            <?php endforeach ?>
        </ul>
        <?php endif ?>
    </header>
    <div class="content"><?= $page->content ?></div>
</article>
PHP;
    }

    private function blogTpl(): string
    {
        return <<<'PHP'
<?php $this->layout('base', ['title' => $page->title]) ?>
<section class="blog-index">
    <h1>Blog</h1>
    <ul>
        <?php foreach ($collections['blog']->posts ?? [] as $post): ?>
        <li>
            <a href="<?= $this->e($post->url) ?>"><?= $this->e($post->title) ?></a>
            <?php if ($post->date): ?>
            <time><?= $post->date->format('Y-m-d') ?></time>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul>
</section>
PHP;
    }

    private function css(): string
    {
        return <<<'CSS'
:root {
    --fg: #1a1a1a;
    --bg: #fdfdfd;
    --accent: #777BB4;
    --muted: #666;
    --border: #e5e5e5;
    --font: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
    --mono: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
}

* { box-sizing: border-box; }

body {
    font-family: var(--font);
    color: var(--fg);
    background: var(--bg);
    max-width: 720px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
    line-height: 1.6;
}

.site-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border);
    margin-bottom: 2rem;
}

.site-title {
    font-weight: 700;
    text-decoration: none;
    color: var(--accent);
    font-size: 1.2rem;
}

.site-footer {
    margin-top: 4rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
    color: var(--muted);
    font-size: 0.875rem;
}

a { color: var(--accent); }

h1, h2, h3 { line-height: 1.25; margin-top: 1.5em; }

time { color: var(--muted); font-size: 0.875rem; }

ul.tags { list-style: none; padding: 0; display: flex; gap: 0.5rem; flex-wrap: wrap; }
ul.tags a {
    text-decoration: none;
    background: #f0f0f0;
    padding: 0.15rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
}

pre {
    background: #1a1a1a;
    color: #f0f0f0;
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    font-family: var(--mono);
    font-size: 0.875rem;
}

code { font-family: var(--mono); font-size: 0.9em; }

p code, li code {
    background: #f0f0f0;
    padding: 0.1em 0.3em;
    border-radius: 0.25rem;
}

blockquote {
    border-left: 3px solid var(--border);
    padding-left: 1rem;
    color: var(--muted);
    margin: 1rem 0;
}
CSS;
    }
}
