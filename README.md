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

## License

MIT
