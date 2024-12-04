<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Registry of plugin hooks.
 *
 * A plugin is a PHP file that returns an associative array mapping hook
 * names to callables:
 *
 *   return [
 *     'beforeBuild' => function (Site $site) { ... },
 *     'onPage'      => function (Page $page): Page { ... return $page; },
 *     'afterBuild'  => function (string $outputDir) { ... },
 *   ];
 *
 * Hooks fire in registration order. onPage callbacks can mutate or replace
 * the page; the returned Page is passed to the next callback.
 */
class PluginManager
{
    public const HOOKS = ['beforeBuild', 'onPage', 'afterBuild'];

    private array $hooks = ['beforeBuild' => [], 'onPage' => [], 'afterBuild' => []];

    public function loadFile(string $absolutePath): void
    {
        if (!file_exists($absolutePath)) {
            return;
        }
        $registered = require $absolutePath;
        if (!is_array($registered)) {
            return;
        }
        foreach ($registered as $hook => $callable) {
            if (!in_array($hook, self::HOOKS, true)) continue;
            if (!is_callable($callable)) continue;
            $this->hooks[$hook][] = $callable;
        }
    }

    public function fire(string $hook, array $args = []): void
    {
        foreach ($this->hooks[$hook] ?? [] as $cb) {
            $cb(...$args);
        }
    }

    public function transformPage(Page $page): Page
    {
        foreach ($this->hooks['onPage'] as $cb) {
            $result = $cb($page);
            if ($result instanceof Page) {
                $page = $result;
            }
        }
        return $page;
    }
}
