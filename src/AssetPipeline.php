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

            $publicName = $relative;
            if ($this->fingerprint && $this->shouldFingerprint($relative)) {
                $hash = substr(md5_file($file->getPathname()), 0, 8);
                $publicName = $this->insertHash($relative, $hash);
            }

            $dest = $targetRoot . '/' . $publicName;
            $this->ensureDir(dirname($dest));
            copy($file->getPathname(), $dest);
            $this->map[$relative] = '/assets/' . $publicName;
        }

        return $this->map;
    }

    private function shouldFingerprint(string $relative): bool
    {
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        return in_array($ext, ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'woff', 'woff2'], true);
    }

    private function insertHash(string $path, string $hash): string
    {
        $info = pathinfo($path);
        $dir = $info['dirname'] === '.' ? '' : $info['dirname'] . '/';
        $base = $info['filename'];
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
        return $dir . $base . '.' . $hash . $ext;
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
