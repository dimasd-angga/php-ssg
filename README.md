# php-ssg

A fast, zero-dependency PHP static site generator. Write Markdown, get a production-ready website. No Node.js, no build tools, no config hell.

## Quick start

```bash
composer global require dimasd-angga/php-ssg

php-ssg new my-site
cd my-site
php-ssg build
```

Output is in `dist/`.

## Writing content

A typical blog post at `content/blog/2024-09-01-hello-world.md`:

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
```

## Templates

Templates are plain PHP files. No new syntax. Your `templates/post.php`:

```php
<?php $this->layout('base', ['title' => $page->title]) ?>

<article>
    <h1><?= $this->e($page->title) ?></h1>
    <time><?= $page->date->format('F j, Y') ?></time>
    <div><?= $page->content ?></div>
</article>
```

## Syntax highlighting

Fenced code blocks with a language hint emit `<pre><code class="language-XX">`.
You can highlight client-side by adding Prism.js or highlight.js to your base
template:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1/themes/prism.min.css">
<script src="https://cdn.jsdelivr.net/npm/prismjs@1/components/prism-core.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1/plugins/autoloader/prism-autoloader.min.js"></script>
```

For server-side highlighting with zero client JS, see `src/SyntaxHighlight.php`
for an example plugin that integrates `scrivo/highlight.php`.

## CLI commands

```bash
php-ssg new <name>          # Scaffold a new site
php-ssg build               # Build for production (output → dist/)
php-ssg build --drafts      # Include draft posts in the build
php-ssg serve               # Build and serve dist/ on http://localhost:8000
php-ssg serve --port=3000   # Custom port
php-ssg watch               # Rebuild on file changes (no server)
php-ssg page <slug>         # Create a new blank page in content/
php-ssg post <title>        # Create a new date-prefixed blog post
php-ssg validate            # Check config and templates for errors
php-ssg clean               # Delete dist/ directory
php-ssg --version           # Show version
```

## License

MIT
