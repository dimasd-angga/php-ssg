<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Represents a single content page.
 *
 * Data is a thin wrapper over the parsed frontmatter plus computed fields:
 * - $slug: relative path from content/ minus extension (e.g. "blog/hello")
 * - $url: public URL where this page will be reachable
 * - $content: rendered HTML body (Markdown converted)
 * - $rawContent: original Markdown source
 * - $sourcePath: absolute filesystem path to the source .md file
 * - $meta: any additional frontmatter fields, accessible via magic __get
 */
class Page
{
    public string $slug;
    public string $url;
    public string $title;
    public ?\DateTimeImmutable $date = null;
    public array $tags = [];
    public string $layout = 'page';
    public string $content = '';
    public string $rawContent = '';
    public string $sourcePath = '';
    public bool $draft = false;
    public ?string $description = null;
    public ?string $excerpt = null;
    public ?string $locale = null;
    public array $meta = [];

    public function __construct(array $data = [], string $rawContent = '', string $sourcePath = '')
    {
        $this->rawContent = $rawContent;
        $this->sourcePath = $sourcePath;

        $known = [
            'slug', 'url', 'title', 'tags', 'layout', 'content',
            'draft', 'description', 'excerpt', 'locale',
        ];

        foreach ($data as $key => $value) {
            if ($key === 'date' && $value !== null) {
                $this->date = $value instanceof \DateTimeImmutable
                    ? $value
                    : new \DateTimeImmutable((string) $value);
                continue;
            }
            if (in_array($key, $known, true)) {
                $this->$key = $value;
                continue;
            }
            $this->meta[$key] = $value;
        }

        if ($this->title === '' || $this->title === null) {
            $this->title = $this->slug === '' ? 'Untitled' : basename($this->slug);
        }
    }

    public function __get(string $key): mixed
    {
        return $this->meta[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->meta[$key]);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->meta[$key] = $value;
    }
}
