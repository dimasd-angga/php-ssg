<?php

declare(strict_types=1);

namespace PhpSsg\Commands;

use PhpSsg\Site;

class BuildCommand
{
    public function run(array $args): int
    {
        $root = getcwd();
        $configPath = $root . '/ssg.config.php';

        if (!file_exists($configPath)) {
            fwrite(STDERR, "No ssg.config.php found in current directory.\n");
            return 1;
        }

        $config = require $configPath;
        if (!is_array($config)) {
            fwrite(STDERR, "ssg.config.php must return an array.\n");
            return 1;
        }

        foreach ($args as $arg) {
            if ($arg === '--drafts') {
                $config['build']['drafts'] = true;
            }
            if ($arg === '--minify') {
                $config['build']['minify'] = true;
            }
            if ($arg === '--parallel') {
                $config['build']['parallel'] = true;
            }
        }

        $start = microtime(true);
        $site = new Site($root, $config);
        $site->build();
        $elapsed = (int) round((microtime(true) - $start) * 1000);

        $pageCount = count($site->pages);
        echo "Built {$pageCount} page(s) in {$elapsed}ms → " . ($config['build']['output'] ?? 'dist') . "/\n";
        return 0;
    }
}
