<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Asset pipeline.
 *
 * Stage 1: copy assets from source to output directory, preserving structure.
 * Stage 2 (added later): content-hash fingerprint each asset for cache busting.
 * Stage 3 (added later): rewrite asset URLs in generated HTML to point at the
 *   fingerprinted filenames.
 *
 * Asset map is exposed via getMap() so templates can resolve the original
 * path (`css/main.css`) to its fingerprinted URL.
 */
class AssetPipeline
{
    private array $map = [];
    private bool $fingerprint = true;

    public function setFingerprintEnabled(bool $enabled): void
    {
        $this->fingerprint = $enabled;
    }

    public function build(string $assetsDir, string $outputDir): array
    {
        $this->map = [];
        if (!is_dir($assetsDir)) {
            return $this->map;
        }

        $targetRoot = rtrim($outputDir, '/') . '/assets';
        $this->ensureDir($targetRoot);

        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($assetsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iter as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) continue;

            $relative = substr($file->getPathname(), strlen($assetsDir) + 1);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            $dest = $targetRoot . '/' . $relative;
            $this->ensureDir(dirname($dest));
            copy($file->getPathname(), $dest);
            $this->map[$relative] = '/assets/' . $relative;
        }

        return $this->map;
    }

    public function getMap(): array
    {
        return $this->map;
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
