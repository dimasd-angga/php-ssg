<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Recursively walks a content directory and yields every Markdown file
 * with its relative slug. The slug is the path relative to the content
 * root, minus the .md extension. `content/blog/hello.md` yields slug
 * `blog/hello`.
 */
class ContentScanner
{
    /**
     * @return iterable<array{path: string, slug: string, relative: string}>
     */
    public function scan(string $root): iterable
    {
        $root = rtrim($root, '/');
        if (!is_dir($root)) {
            return;
        }

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if ($ext !== 'md' && $ext !== 'markdown') continue;

            $relative = substr($file->getPathname(), strlen($root) + 1);
            $slug = preg_replace('/\.(md|markdown)$/i', '', $relative);

            yield [
                'path' => $file->getPathname(),
                'slug' => $slug,
                'relative' => $relative,
            ];
        }
    }
}
