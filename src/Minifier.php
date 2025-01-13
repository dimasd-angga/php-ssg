<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Conservative HTML minifier.
 *
 * Strips whitespace between block-level tags and collapses runs of internal
 * whitespace, but preserves the contents of <pre>, <code>, <textarea>, and
 * <script> blocks verbatim. Comments are removed except for IE conditional
 * comments (rare today but cheap to keep).
 *
 * Designed to be safe-by-default: a wrong minification is worse than a
 * mildly larger file, so unknown cases are left untouched.
 */
class Minifier
{
    public function minify(string $html): string
    {
        // Pull out pre/code/textarea/script blocks before whitespace-collapsing.
        $preserved = [];
        $html = preg_replace_callback(
            '#<(pre|code|textarea|script|style)\b[^>]*>.*?</\1>#is',
            function ($m) use (&$preserved) {
                $i = count($preserved);
                $preserved[] = $m[0];
                return "\x01PRESERVE{$i}\x01";
            },
            $html
        );

        // Strip HTML comments (keep IE conditionals).
        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);

        // Collapse whitespace between tags.
        $html = preg_replace('/>\s+</', '><', $html);

        // Collapse internal runs of whitespace.
        $html = preg_replace('/[ \t]+/', ' ', $html);
        $html = preg_replace('/\n\s*/', "\n", $html);

        $html = trim($html);

        foreach ($preserved as $i => $block) {
            $html = str_replace("\x01PRESERVE{$i}\x01", $block, $html);
        }

        return $html;
    }
}
