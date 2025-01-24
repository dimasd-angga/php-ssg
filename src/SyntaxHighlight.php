<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Hooks for integrating a syntax highlighter.
 *
 * The Markdown parser already emits <pre><code class="language-XX"> on
 * fenced code blocks with a language hint. From there you have two paths:
 *
 *   (1) Client-side: load Prism.js or highlight.js in your template and it
 *       will pick up the language- classes automatically. Zero PHP work.
 *
 *   (2) Server-side: register an onPage plugin that runs scrivo/highlight.php
 *       on each code block. This keeps the build pure-PHP but adds a
 *       Composer dependency. Example below.
 *
 * Example onPage plugin enabling server-side highlight.php (requires
 * `composer require scrivo/highlight.php` in the consumer's project):
 *
 *   return [
 *     'onPage' => function (PhpSsg\Page $p) {
 *       $hl = new Highlight\Highlighter();
 *       $p->content = preg_replace_callback(
 *         '#<pre><code class="language-(\w+)">(.+?)</code></pre>#s',
 *         function ($m) use ($hl) {
 *           try {
 *             $r = $hl->highlight($m[1], html_entity_decode($m[2]));
 *             return '<pre><code class="hljs language-' . $r->language . '">' . $r->value . '</code></pre>';
 *           } catch (\Throwable) {
 *             return $m[0];
 *           }
 *         }, $p->content);
 *       return $p;
 *     },
 *   ];
 */
final class SyntaxHighlight
{
    public const SUPPORTED_LANGUAGES = [
        'php', 'javascript', 'typescript', 'go', 'rust', 'python', 'ruby',
        'bash', 'shell', 'sql', 'html', 'css', 'json', 'yaml', 'markdown',
        'java', 'csharp', 'kotlin', 'swift', 'c', 'cpp',
    ];

    public static function detect(string $code): ?string
    {
        $trim = ltrim($code);
        if (str_starts_with($trim, '<?php')) return 'php';
        if (str_starts_with($trim, '<!doctype') || str_starts_with($trim, '<html')) return 'html';
        if (str_starts_with($trim, '{') || str_starts_with($trim, '[')) return 'json';
        if (str_starts_with($trim, '#!')) return 'bash';
        return null;
    }
}
