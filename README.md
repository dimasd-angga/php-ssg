# php-ssg ⚡

> A fast, zero-dependency PHP static site generator. Write Markdown, get a production-ready website. No Node.js, no build tools, no config hell.

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/dimasd-angga/php-ssg.svg)](https://packagist.org/packages/dimasd-angga/php-ssg)
[![Tests](https://img.shields.io/github/actions/workflow/status/dimasd-angga/php-ssg/ci.yml?label=tests)](https://github.com/dimasd-angga/php-ssg/actions)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](CONTRIBUTING.md)

---

## Why php-ssg?

Every PHP developer already has PHP installed. Yet building a static site means installing Node.js, fighting npm, and debugging webpack configs just to render Markdown into HTML.

php-ssg fixes that:

- **Zero Node.js.** No npm, no bundlers, no `node_modules/`. Just PHP.
- **Real templating.** Uses PHP's native templating — no new syntax to learn.
- **Frontmatter + Markdown.** Write content in Markdown with YAML frontmatter for metadata.
- **Asset pipeline.** CSS and JS are copied and fingerprinted automatically.
- **Watch mode.** `php-ssg watch` rebuilds on file change.
- **Deploy anywhere.** Output is plain HTML — rsync it, put it on S3, Netlify, GitHub Pages.

---

## Quick start

```bash
# Install
composer global require dimasd-angga/php-ssg

# Create a new site
php-ssg new my-site
cd my-site

# Start dev server with live reload
php-ssg serve

# Build for production
php-ssg build
```

Your site is in `dist/`. Done.

---

## Features

| Feature | Details |
|---|---|
| **Markdown parsing** | Useful CommonMark subset built-in; pluggable for full spec compliance |
| **Frontmatter** | YAML frontmatter for title, date, tags, layout, custom vars |
| **Templating** | Native PHP templates (`.php` layouts) — no new syntax |
| **Collections** | Blog posts, docs pages, portfolios — auto-paginated |
| **Asset pipeline** | Copy + content-hash fingerprinting for CSS/JS/images |
| **Watch mode** | Cross-platform file watcher, rebuilds in < 100ms |
| **Dev server** | Built-in PHP dev server |
| **Sitemap** | `sitemap.xml` generated automatically |
| **RSS feed** | `feed.xml` generated for any collection |
| **Taxonomies** | Tags and categories with archive pages |
| **Multilingual** | i18n support via locale-prefixed content directories |
| **Plugin hooks** | `beforeBuild`, `afterBuild`, `onPage` hooks for extensions |

---

## Project structure

```
my-site/
├── content/                  # Your Markdown content
│   ├── index.md              # Homepage
│   ├── about.md
│   └── blog/
│       ├── 2024-09-01-hello-world.md
│       └── 2024-10-15-second-post.md
│
├── templates/                # PHP layout files
│   ├── base.php              # Base HTML shell
│   ├── page.php              # Default page layout
│   ├── post.php              # Blog post layout
│   └── partials/
│       ├── header.php
│       └── footer.php
│
├── assets/                   # Static assets
│   ├── css/
│   │   └── main.css
│   ├── js/
│   │   └── app.js
│   └── images/
│
├── plugins/                  # Custom plugin hooks
│   └── my-plugin.php
│
├── ssg.config.php            # Site configuration
└── dist/                     # Generated output (git-ignored)
```

---

## Writing content

A typical blog post in `content/blog/2024-09-01-hello-world.md`:

```markdown
---
title: Hello World
date: 2024-09-01
tags: [php, web, tutorial]
layout: post
description: My first post built with php-ssg.
---

# Hello World

This is my first post. Write **Markdown** here — it gets converted to clean HTML.

## Code blocks work too

​```php
<?php
echo "Hello from php-ssg!";
​```
```

---

## Templating

Templates are plain PHP files. No new syntax. Your `templates/post.php`:

```php
<?php $this->layout('base', ['title' => $page->title]) ?>

<article class="post">
    <header>
        <h1><?= $this->e($page->title) ?></h1>
        <time><?= $page->date->format('F j, Y') ?></time>

        <?php foreach ($page->tags as $tag): ?>
            <a href="/tags/<?= $tag ?>"><?= $tag ?></a>
        <?php endforeach ?>
    </header>

    <div class="content">
        <?= $page->content ?>
    </div>
</article>
```

---

## Configuration

`ssg.config.php` at your project root:

```php
<?php

return [
    'site' => [
        'name'    => 'My Site',
        'url'     => 'https://example.com',
        'locale'  => 'en',
        'author'  => 'Your Name',
    ],

    'build' => [
        'output'    => 'dist',
        'baseUrl'   => '/',
        'minify'    => true,          // Minify HTML output
        'drafts'    => false,         // Include draft posts
    ],

    'collections' => [
        'blog' => [
            'path'      => 'content/blog',
            'layout'    => 'post',
            'sortBy'    => 'date',
            'perPage'   => 10,
            'rss'       => true,
        ],
    ],

    'plugins' => [
        'plugins/my-plugin.php',
    ],
];
```

---

## Asset pipeline

Place assets in `assets/`. They are automatically:

1. Copied to `dist/assets/`
2. Content-hashed for cache busting: `main.css` → `main.abc12345.css`
3. References updated in generated HTML

```php
<!-- In your template — helper handles the hash automatically -->
<link rel="stylesheet" href="<?= $this->asset('css/main.css') ?>">
<script src="<?= $this->asset('js/app.js') ?>"></script>
```

---

## Collections and pagination

Define a collection in config, then use it in templates:

```php
<?php // templates/blog.php — auto-receives $collection and $pagination ?>

<?php foreach ($collection->posts as $post): ?>
    <article>
        <h2><a href="<?= $post->url ?>"><?= $post->title ?></a></h2>
        <p><?= $post->excerpt ?></p>
    </article>
<?php endforeach ?>
```

---

## Plugin hooks

Extend php-ssg without forking it:

```php
<?php // plugins/my-plugin.php

use PhpSsg\Page;
use PhpSsg\Site;

return [
    'beforeBuild' => function (Site $site) {
        // Runs before the build starts
        // Modify $site->pages, add virtual pages, etc.
    },

    'onPage' => function (Page $page) {
        // Runs for every page — transform content, add metadata
        $page->readingTime = ceil(str_word_count($page->rawContent) / 200);
        return $page;
    },

    'afterBuild' => function (string $outputDir) {
        // Runs after build completes — submit sitemap, notify CDN, etc.
    },
];
```

---

## CLI commands

```bash
php-ssg new <name>      # Scaffold a new site
php-ssg build           # Build for production (output → dist/)
php-ssg build --drafts  # Include draft posts
php-ssg build --minify  # Minify HTML output
php-ssg build --parallel # Parallel render via Fibers (large sites)
php-ssg serve           # Start dev server (default: localhost:8000)
php-ssg serve --port=3000
php-ssg watch           # Watch only, no server
php-ssg clean           # Delete dist/ directory
php-ssg page <slug>     # Create a new blank page in content/
php-ssg post <title>    # Create a new date-prefixed blog post
php-ssg validate        # Check config and templates for errors
```

---

## Performance

php-ssg builds fast because it does one pass per file with no intermediate representation:

| Site size | Build time |
|---|---|
| 10 pages | ~5ms |
| 100 pages | ~20ms |
| 500 pages | ~150ms |
| 1,000 pages | ~400ms |

Tested on PHP 8.4, MacBook M-series. Larger sites benefit from the `--parallel` flag (PHP 8.1+ Fibers).

---

## Deploying

### GitHub Pages

See `examples/deploy/github-pages.yml` for a ready-to-copy workflow file.

### Netlify

See `examples/deploy/netlify.toml` for a ready-to-copy config.

### Rsync to a VPS

```bash
php-ssg build
rsync -avz dist/ user@yourserver.com:/var/www/html/
```

---

## Comparison

| | php-ssg | Hugo | Jekyll | Eleventy |
|---|---|---|---|---|
| Language | PHP | Go | Ruby | Node.js |
| Requires Node.js | ✗ | ✗ | ✗ | ✓ |
| Requires Ruby | ✗ | ✗ | ✓ | ✗ |
| PHP templates | ✓ | ✗ | ✗ | ✗ |
| Composer installable | ✓ | ✗ | ✗ | ✗ |
| Plugin API | ✓ | ✓ | ✓ | ✓ |

If your stack is PHP, php-ssg fits like a glove. No context switching, no new runtimes.

---

## Roadmap

- [ ] Incremental builds (only rebuild changed pages)
- [ ] Image optimization pipeline (resize, WebP conversion)
- [ ] Admin UI — browser-based content editor
- [ ] Tailwind CSS integration (JIT via standalone CLI binary)
- [ ] Algolia search integration plugin
- [ ] WordPress importer (migrate existing WP site)

---

## Contributing

Contributions are very welcome — see [CONTRIBUTING.md](CONTRIBUTING.md).

If php-ssg saved you from installing Node.js, a ⭐ star goes a long way.

---

## License

MIT © [Dimas D. Angga](https://github.com/dimasd-angga)

<!-- SEO keywords (indexed by GitHub, not shown to readers) -->
<!-- php static site generator, php ssg, static site generator php,
     php markdown site, php blog generator, php website generator,
     static site generator without node, php cms alternative,
     php templating engine, php blog engine, php frontmatter,
     composer static site, php-ssg, laravel blade static site,
     php html generator, php website builder, no nodejs static site -->
