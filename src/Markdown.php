<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Lightweight Markdown to HTML converter.
 *
 * Implements a useful subset of CommonMark without external dependencies:
 * headings, paragraphs, bold/italic, links, images, inline code, code fences,
 * blockquotes, unordered/ordered lists, horizontal rules. Sufficient for blogs,
 * docs, and personal sites. Users wanting full CommonMark spec compliance can
 * swap this for league/commonmark via a plugin.
 */
class Markdown
{
    public function toHtml(string $markdown): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $markdown);

        $codeBlocks = [];
        $text = preg_replace_callback(
            '/```(\w*)\n(.*?)```/s',
            function ($m) use (&$codeBlocks) {
                $lang = $m[1] !== '' ? ' class="language-' . $m[1] . '"' : '';
                $code = htmlspecialchars($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $placeholder = "\x00CODE" . count($codeBlocks) . "\x00";
                $codeBlocks[] = '<pre><code' . $lang . '>' . $code . '</code></pre>';
                return $placeholder;
            },
            $text
        );

        $lines = explode("\n", $text);
        $html = [];
        $inList = false;
        $listType = null;
        $inBlockquote = false;
        $paraBuf = [];

        $flushPara = function () use (&$paraBuf, &$html) {
            if ($paraBuf === []) return;
            $html[] = '<p>' . $this->inline(implode(' ', $paraBuf)) . '</p>';
            $paraBuf = [];
        };

        $closeList = function () use (&$inList, &$listType, &$html) {
            if ($inList) {
                $html[] = '</' . $listType . '>';
                $inList = false;
                $listType = null;
            }
        };

        $closeBlockquote = function () use (&$inBlockquote, &$html) {
            if ($inBlockquote) {
                $html[] = '</blockquote>';
                $inBlockquote = false;
            }
        };

        foreach ($lines as $line) {
            if (preg_match('/\x00CODE\d+\x00/', $line)) {
                $flushPara();
                $closeList();
                $closeBlockquote();
                $html[] = $line;
                continue;
            }

            if (trim($line) === '') {
                $flushPara();
                $closeList();
                $closeBlockquote();
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
                $flushPara();
                $closeList();
                $closeBlockquote();
                $level = strlen($m[1]);
                $html[] = "<h{$level}>" . $this->inline($m[2]) . "</h{$level}>";
                continue;
            }

            if (preg_match('/^(\-{3,}|\*{3,}|_{3,})$/', trim($line))) {
                $flushPara();
                $closeList();
                $closeBlockquote();
                $html[] = '<hr>';
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $flushPara();
                $closeList();
                if (!$inBlockquote) {
                    $html[] = '<blockquote>';
                    $inBlockquote = true;
                }
                $html[] = '<p>' . $this->inline($m[1]) . '</p>';
                continue;
            }

            if (preg_match('/^[\-\*\+]\s+(.*)$/', $line, $m)) {
                $flushPara();
                $closeBlockquote();
                if (!$inList || $listType !== 'ul') {
                    $closeList();
                    $html[] = '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }
                $html[] = '<li>' . $this->inline($m[1]) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.*)$/', $line, $m)) {
                $flushPara();
                $closeBlockquote();
                if (!$inList || $listType !== 'ol') {
                    $closeList();
                    $html[] = '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }
                $html[] = '<li>' . $this->inline($m[1]) . '</li>';
                continue;
            }

            $closeBlockquote();
            $closeList();
            $paraBuf[] = $line;
        }

        $flushPara();
        $closeList();
        $closeBlockquote();

        $output = implode("\n", $html);

        foreach ($codeBlocks as $i => $block) {
            $output = str_replace("\x00CODE{$i}\x00", $block, $output);
        }

        return $output;
    }

    private function inline(string $text): string
    {
        $inlineCodes = [];
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$inlineCodes) {
            $placeholder = "\x00IC" . count($inlineCodes) . "\x00";
            $inlineCodes[] = '<code>' . htmlspecialchars($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</code>';
            return $placeholder;
        }, $text);

        $text = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $text);
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text);
        $text = preg_replace('/(?<!_)_([^_]+)_(?!_)/', '<em>$1</em>', $text);

        foreach ($inlineCodes as $i => $code) {
            $text = str_replace("\x00IC{$i}\x00", $code, $text);
        }

        return $text;
    }
}
