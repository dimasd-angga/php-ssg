<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Filesystem watcher.
 *
 * Polls a set of directories on a configurable interval and reports back
 * any file additions, removals, or modifications. The choice to poll rather
 * than use inotify keeps the implementation cross-platform (macOS, Linux,
 * Windows) without compiled extensions.
 *
 * Granularity is bounded by the poll interval (~250ms by default), which is
 * fine for content/template editing where sub-100ms latency is not required.
 */
class Watcher
{
    private array $dirs;
    private int $intervalMs;
    private array $snapshot = [];

    public function __construct(array $dirs, int $intervalMs = 250)
    {
        $this->dirs = array_filter($dirs, 'is_dir');
        $this->intervalMs = $intervalMs;
        $this->snapshot = $this->takeSnapshot();
    }

    public function start(callable $onChange): void
    {
        while (true) {
            usleep($this->intervalMs * 1000);
            $now = $this->takeSnapshot();
            $changes = $this->diff($this->snapshot, $now);
            if ($changes !== []) {
                $onChange($changes);
                $this->snapshot = $now;
            }
        }
    }

    /**
     * Take one snapshot and compare against the stored one; non-blocking.
     * Returns the changed paths and updates the internal snapshot.
     */
    public function tick(): array
    {
        $now = $this->takeSnapshot();
        $changes = $this->diff($this->snapshot, $now);
        if ($changes !== []) {
            $this->snapshot = $now;
        }
        return $changes;
    }

    private function takeSnapshot(): array
    {
        $snap = [];
        foreach ($this->dirs as $dir) {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iter as $f) {
                /** @var \SplFileInfo $f */
                if (!$f->isFile()) continue;
                $snap[$f->getPathname()] = $f->getMTime();
            }
        }
        return $snap;
    }

    private function diff(array $old, array $new): array
    {
        $changes = [];
        foreach ($new as $path => $mtime) {
            if (!isset($old[$path])) {
                $changes[] = ['type' => 'added', 'path' => $path];
            } elseif ($old[$path] !== $mtime) {
                $changes[] = ['type' => 'modified', 'path' => $path];
            }
        }
        foreach ($old as $path => $_) {
            if (!isset($new[$path])) {
                $changes[] = ['type' => 'removed', 'path' => $path];
            }
        }
        return $changes;
    }
}
