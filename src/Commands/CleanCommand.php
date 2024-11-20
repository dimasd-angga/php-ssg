<?php

declare(strict_types=1);

namespace PhpSsg\Commands;

class CleanCommand
{
    public function run(array $args): int
    {
        $root = getcwd();
        $configPath = $root . '/ssg.config.php';
        $output = 'dist';
        if (file_exists($configPath)) {
            $config = require $configPath;
            $output = $config['build']['output'] ?? 'dist';
        }

        $target = $root . '/' . $output;
        if (!is_dir($target)) {
            echo "{$output}/ does not exist — nothing to clean.\n";
            return 0;
        }

        $this->rrmdir($target);
        echo "Removed {$output}/\n";
        return 0;
    }

    private function rrmdir(string $dir): void
    {
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }
}
