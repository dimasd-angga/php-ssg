<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Iterable, countable wrapper around an ordered list of Page objects.
 * Returned from Site::getCollection() and exposed in templates as
 * $collections[$name]. Implements Countable + IteratorAggregate so
 * templates can use it in foreach and count() without extra ceremony.
 */
class Collection implements \IteratorAggregate, \Countable
{
    public string $name;
    /** @var Page[] */
    public array $posts;
    public array $config;

    public function __construct(string $name, array $posts, array $config = [])
    {
        $this->name = $name;
        $this->posts = $posts;
        $this->config = $config;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->posts);
    }

    public function count(): int
    {
        return count($this->posts);
    }

    public function latest(int $n): array
    {
        return array_slice($this->posts, 0, $n);
    }

    public function byTag(string $tag): array
    {
        return array_values(array_filter(
            $this->posts,
            fn(Page $p) => in_array($tag, $p->tags ?? [], true)
        ));
    }
}
