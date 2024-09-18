<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Minimal YAML frontmatter parser.
 *
 * Supports a useful subset of YAML used in static site frontmatter:
 * - Scalar values: strings, integers, floats, booleans, null
 * - Quoted strings (single and double)
 * - Flow-style sequences: tags: [a, b, c]
 * - Block-style sequences (one item per line with leading "- ")
 * - ISO-8601 dates (YYYY-MM-DD)
 *
 * Returns ['data' => array, 'content' => string].
 */
class Frontmatter
{
    public function parse(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);

        if (!str_starts_with($raw, "---\n")) {
            return ['data' => [], 'content' => $raw];
        }

        $end = strpos($raw, "\n---", 4);
        if ($end === false) {
            // unterminated frontmatter — treat whole file as content
            return ['data' => [], 'content' => $raw];
        }

        $yaml = substr($raw, 4, $end - 4);
        $content = substr($raw, $end + 4);
        if (str_starts_with($content, "\n")) {
            $content = substr($content, 1);
        }

        try {
            $data = $this->parseYaml($yaml);
        } catch (\Throwable) {
            // malformed YAML — surface as empty data rather than crashing the build
            $data = [];
        }

        return [
            'data' => $data,
            'content' => $content,
        ];
    }

    private function parseYaml(string $yaml): array
    {
        $data = [];
        $lines = explode("\n", $yaml);
        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];

            if (trim($line) === '' || str_starts_with(trim($line), '#')) {
                $i++;
                continue;
            }

            if (!preg_match('/^([A-Za-z_][A-Za-z0-9_-]*):\s*(.*)$/', $line, $m)) {
                $i++;
                continue;
            }

            $key = $m[1];
            $value = trim($m[2]);

            if ($value === '') {
                $items = [];
                $j = $i + 1;
                while ($j < $count && preg_match('/^\s+-\s*(.*)$/', $lines[$j], $im)) {
                    $items[] = $this->parseScalar(trim($im[1]));
                    $j++;
                }
                if ($items !== []) {
                    $data[$key] = $items;
                    $i = $j;
                    continue;
                }
                $data[$key] = null;
                $i++;
                continue;
            }

            if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $inner = trim(substr($value, 1, -1));
                if ($inner === '') {
                    $data[$key] = [];
                } else {
                    $data[$key] = array_map(
                        fn($v) => $this->parseScalar(trim($v)),
                        $this->splitFlowList($inner)
                    );
                }
                $i++;
                continue;
            }

            $data[$key] = $this->parseScalar($value);
            $i++;
        }

        return $data;
    }

    private function splitFlowList(string $inner): array
    {
        $parts = [];
        $depth = 0;
        $buf = '';
        $inQuote = null;

        for ($i = 0; $i < strlen($inner); $i++) {
            $c = $inner[$i];
            if ($inQuote !== null) {
                $buf .= $c;
                if ($c === $inQuote) $inQuote = null;
                continue;
            }
            if ($c === '"' || $c === "'") {
                $inQuote = $c;
                $buf .= $c;
                continue;
            }
            if ($c === '[') $depth++;
            if ($c === ']') $depth--;
            if ($c === ',' && $depth === 0) {
                $parts[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $c;
        }
        if ($buf !== '') $parts[] = $buf;
        return $parts;
    }

    private function parseScalar(string $value): mixed
    {
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        $lower = strtolower($value);
        if ($lower === 'true' || $lower === 'yes') return true;
        if ($lower === 'false' || $lower === 'no') return false;
        if ($lower === 'null' || $value === '~' || $value === '') return null;

        if (preg_match('/^-?\d+$/', $value)) return (int) $value;
        if (preg_match('/^-?\d+\.\d+$/', $value)) return (float) $value;

        return $value;
    }
}
