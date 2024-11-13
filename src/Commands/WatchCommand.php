<?php

declare(strict_types=1);

namespace PhpSsg\Commands;

use PhpSsg\Site;
use PhpSsg\Watcher;

/**
 * Watches content/, templates/, assets/, and ssg.config.php for changes
 * and re-runs the build. Used directly via `php-ssg watch` and also
 * embedded in `php-ssg serve` so editing files reflects in the browser
 * after a manual refresh.
 */
class WatchCommand
{
    public function run(array $args): int
    {
        $root = getcwd();
        $configPath = $root . '/ssg.config.php';
        if (!file_exists($configPath)) {
            fwrite(STDERR, "No ssg.config.php found in current directory.\n");
            return 1;
        }

        $dirs = array_filter([
            $root . '/content',
            $root . '/templates',
            $root . '/assets',
            $root . '/plugins',
        ], 'is_dir');

        $config = require $configPath;

        echo "Initial build...\n";
        $this->rebuild($root, $config);

        echo "Watching for changes (Ctrl+C to stop)...\n";
        $watcher = new Watcher($dirs);
        $watcher->start(function ($changes) use ($root, &$config, $configPath) {
            $config = require $configPath;
            $msg = count($changes) === 1
                ? basename($changes[0]['path']) . ' ' . $changes[0]['type']
                : count($changes) . ' files changed';
            echo "[" . date('H:i:s') . "] {$msg} — rebuilding...\n";
            $this->rebuild($root, $config);
        });

        return 0;
    }

    private function rebuild(string $root, array $config): void
    {
        $start = microtime(true);
        try {
            (new Site($root, $config))->build();
            $ms = (int) round((microtime(true) - $start) * 1000);
            echo "  ok ({$ms}ms)\n";
        } catch (\Throwable $e) {
            echo "  BUILD FAILED: " . $e->getMessage() . "\n";
        }
    }
}
