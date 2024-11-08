<?php

declare(strict_types=1);

namespace PhpSsg\Commands;

use PhpSsg\Site;

/**
 * Runs an initial build, then starts PHP's built-in web server pointed at
 * the output directory. Watch mode (added in WatchCommand) handles rebuilds.
 */
class ServeCommand
{
    public function run(array $args): int
    {
        $port = 8000;
        $host = 'localhost';
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--port=')) {
                $port = (int) substr($arg, 7);
            }
            if (str_starts_with($arg, '--host=')) {
                $host = substr($arg, 7);
            }
        }

        $root = getcwd();
        $configPath = $root . '/ssg.config.php';
        if (!file_exists($configPath)) {
            fwrite(STDERR, "No ssg.config.php found in current directory.\n");
            return 1;
        }
        $config = require $configPath;
        $outputDir = $root . '/' . ($config['build']['output'] ?? 'dist');

        echo "Building...\n";
        (new BuildCommand())->run([]);

        echo "Serving {$outputDir} at http://{$host}:{$port}\n";
        echo "Press Ctrl+C to stop.\n";

        $cmd = sprintf('php -S %s:%d -t %s', escapeshellarg($host), $port, escapeshellarg($outputDir));
        passthru($cmd, $status);
        return $status;
    }
}
