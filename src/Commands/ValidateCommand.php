<?php

declare(strict_types=1);

namespace PhpSsg\Commands;

/**
 * Sanity-checks an ssg.config.php and the template directory before a build.
 * Reports problems and exits non-zero if any are fatal.
 */
class ValidateCommand
{
    public function run(array $args): int
    {
        $root = getcwd();
        $configPath = $root . '/ssg.config.php';
        $errors = [];
        $warnings = [];

        if (!file_exists($configPath)) {
            fwrite(STDERR, "ssg.config.php not found.\n");
            return 1;
        }

        $config = require $configPath;
        if (!is_array($config)) {
            fwrite(STDERR, "ssg.config.php must return an array.\n");
            return 1;
        }

        if (empty($config['site']['name'])) {
            $warnings[] = "site.name is empty";
        }
        if (empty($config['site']['url'])) {
            $warnings[] = "site.url is empty (sitemap and RSS will be skipped)";
        }

        $templatesDir = $root . '/templates';
        if (!is_dir($templatesDir)) {
            $errors[] = "templates/ directory is missing";
        } else {
            foreach (['base.php', 'page.php'] as $required) {
                if (!file_exists($templatesDir . '/' . $required)) {
                    $warnings[] = "templates/{$required} not found";
                }
            }
        }

        if (!is_dir($root . '/content')) {
            $errors[] = "content/ directory is missing";
        }

        foreach ($config['collections'] ?? [] as $name => $def) {
            $path = $root . '/' . ($def['path'] ?? "content/{$name}");
            if (!is_dir($path)) {
                $warnings[] = "collection '{$name}' path '{$def['path']}' does not exist";
            }
            $layout = $def['layout'] ?? null;
            if ($layout && !file_exists($templatesDir . '/' . $layout . '.php')) {
                $warnings[] = "collection '{$name}' layout '{$layout}' template missing";
            }
        }

        foreach ($warnings as $w) {
            echo "warning: {$w}\n";
        }
        foreach ($errors as $e) {
            echo "error: {$e}\n";
        }

        if ($errors !== []) {
            echo "Validation failed.\n";
            return 1;
        }
        echo "Validation passed" . ($warnings === [] ? '.' : ' with ' . count($warnings) . ' warning(s).') . "\n";
        return 0;
    }
}
