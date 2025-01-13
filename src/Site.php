<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Top-level build orchestrator.
 *
 * Reads ssg.config.php, scans content/, parses Markdown + frontmatter,
 * applies templates, runs the asset pipeline, and writes the output to dist/.
 *
 * Public properties (pages, collections) are exposed for plugin hooks to
 * inspect and mutate before the build finalizes.
 */
class Site
{
    public array $config;
    public array $pages = [];
    public array $collections = [];
    public string $root;
    public string $output;

    public array $tagIndex = [];

    private Frontmatter $frontmatter;
    private Markdown $markdown;
    private Template $template;
    private AssetPipeline $assets;
    private ContentScanner $scanner;
    private Taxonomy $taxonomy;
    private PluginManager $plugins;

    public function __construct(string $root, array $config)
    {
        $this->root = rtrim($root, '/');
        $this->config = $config;
        $this->output = $this->root . '/' . ($config['build']['output'] ?? 'dist');

        $this->frontmatter = new Frontmatter();
        $this->markdown = new Markdown();
        $this->scanner = new ContentScanner();
        $this->taxonomy = new Taxonomy();
        $this->plugins = new PluginManager();
        $this->assets = new AssetPipeline();
        $this->assets->setFingerprintEnabled(($config['build']['fingerprint'] ?? true) !== false);

        $globals = ['site' => (object) ($config['site'] ?? [])];
        $this->template = new Template($this->root . '/templates', $globals);
    }

    public function build(): void
    {
        $this->cleanOutput();
        $this->loadPlugins();
        $this->runHooks('beforeBuild', [$this]);

        $assetMap = $this->assets->build($this->root . '/assets', $this->output);
        $this->template->setAssetMap($assetMap);

        $this->loadPages();
        $this->buildCollections();
        $this->tagIndex = $this->taxonomy->tagIndex($this->pages);

        foreach ($this->pages as $page) {
            $page = $this->runOnPage($page);
            $this->renderPage($page);
        }

        $this->renderTagArchives();
        $this->renderSitemap();
        $this->renderRssFeeds();

        $this->runHooks('afterBuild', [$this->output]);
    }

    private function renderRssFeeds(): void
    {
        $siteUrl = $this->config['site']['url'] ?? '';
        if ($siteUrl === '') return;

        $rss = new Rss();
        foreach ($this->config['collections'] ?? [] as $name => $def) {
            if (!($def['rss'] ?? false)) continue;
            $collection = $this->collections[$name] ?? null;
            if ($collection === null) continue;
            $posts = $collection->posts;
            $collectionUrl = '/' . ltrim($def['path'] ?? "content/{$name}", 'content/') . '/';
            $title = ucfirst($name);
            $xml = $rss->generate($posts, $this->config['site'] ?? [], $collectionUrl, $title);

            $feedPath = $this->output . rtrim($collectionUrl, '/') . '/feed.xml';
            $this->ensureDir(dirname($feedPath));
            file_put_contents($feedPath, $xml);
        }
    }

    private function renderSitemap(): void
    {
        $siteUrl = $this->config['site']['url'] ?? '';
        if ($siteUrl === '') return;
        $sitemap = new Sitemap();
        file_put_contents($this->output . '/sitemap.xml', $sitemap->generate($this->pages, $siteUrl));
    }

    private function renderTagArchives(): void
    {
        $layout = 'tag';
        if (!file_exists($this->root . '/templates/' . $layout . '.php')) {
            return;
        }
        foreach ($this->tagIndex as $tag => $pages) {
            $slug = $this->taxonomy->slugify($tag);
            $vpage = new Page([
                'slug' => 'tags/' . $slug,
                'url' => $this->slugToUrl('tags/' . $slug),
                'title' => 'Tag: ' . $tag,
                'layout' => $layout,
            ]);
            $html = $this->template->render($layout, [
                'page' => $vpage,
                'tag' => $tag,
                'posts' => $pages,
                'site' => (object) ($this->config['site'] ?? []),
                'collections' => $this->collections,
            ]);
            $out = $this->slugToOutputPath('tags/' . $slug);
            $this->ensureDir(dirname($out));
            file_put_contents($out, $html);
        }
    }

    public function getTemplate(): Template
    {
        return $this->template;
    }

    public function getAssetMap(): array
    {
        return $this->assets->getMap();
    }

    private function loadPages(): void
    {
        $contentDir = $this->root . '/content';
        $includeDrafts = (bool) ($this->config['build']['drafts'] ?? false);

        foreach ($this->scanner->scan($contentDir) as $file) {
            $raw = file_get_contents($file['path']);
            $parsed = $this->frontmatter->parse($raw);
            $data = $parsed['data'];
            $data['slug'] = $file['slug'];
            $data['url'] = $this->slugToUrl($file['slug']);

            $page = new Page($data, $parsed['content'], $file['path']);
            $page->content = $this->markdown->toHtml($parsed['content']);

            $basename = basename($file['slug']);
            if (preg_match('/^(\d{4}-\d{2}-\d{2})-(.*)$/', $basename, $dm)) {
                if ($page->date === null) {
                    try {
                        $page->date = new \DateTimeImmutable($dm[1]);
                    } catch (\Throwable) {
                        // ignore; date stays null
                    }
                }
                // Always strip the date prefix from the URL slug so the post
                // appears at /blog/welcome/ rather than /blog/2024-01-01-welcome/.
                $cleanSlug = preg_replace('/[^\/]+$/', $dm[2], $page->slug);
                $page->slug = $cleanSlug;
                $page->url = $this->slugToUrl($cleanSlug);
            }

            if ($page->draft && !$includeDrafts) {
                continue;
            }

            $this->pages[] = $page;
        }
    }

    private function buildCollections(): void
    {
        foreach ($this->config['collections'] ?? [] as $name => $def) {
            $path = $def['path'] ?? "content/{$name}";
            $prefix = preg_replace('#^content/#', '', $path);
            $items = array_values(array_filter(
                $this->pages,
                fn(Page $p) => str_starts_with($p->slug, $prefix . '/')
            ));

            $sortBy = $def['sortBy'] ?? 'date';
            usort($items, function (Page $a, Page $b) use ($sortBy) {
                if ($sortBy === 'date') {
                    $ad = $a->date?->getTimestamp() ?? 0;
                    $bd = $b->date?->getTimestamp() ?? 0;
                    return $bd <=> $ad;
                }
                return strcmp((string) ($a->$sortBy ?? ''), (string) ($b->$sortBy ?? ''));
            });

            $defaultLayout = $def['layout'] ?? null;
            if ($defaultLayout) {
                foreach ($items as $item) {
                    if ($item->layout === 'page') {
                        $item->layout = $defaultLayout;
                    }
                }
            }

            $this->collections[$name] = new Collection($name, $items, $def);
        }
    }

    public function getCollection(string $name): ?Collection
    {
        return $this->collections[$name] ?? null;
    }

    private function renderPage(Page $page): void
    {
        $html = $this->template->render($page->layout, [
            'page' => $page,
            'site' => (object) ($this->config['site'] ?? []),
            'collections' => $this->collections,
        ]);

        if ($this->config['build']['minify'] ?? false) {
            $html = (new Minifier())->minify($html);
        }

        $outPath = $this->slugToOutputPath($page->slug);
        $this->ensureDir(dirname($outPath));
        file_put_contents($outPath, $html);
    }

    private function slugToUrl(string $slug): string
    {
        $base = $this->config['build']['baseUrl'] ?? '/';
        $base = '/' . trim($base, '/');
        $base = $base === '/' ? '' : $base;

        if ($slug === 'index') return $base . '/';
        return $base . '/' . $slug . '/';
    }

    private function slugToOutputPath(string $slug): string
    {
        if ($slug === 'index') {
            return $this->output . '/index.html';
        }
        return $this->output . '/' . $slug . '/index.html';
    }

    private function cleanOutput(): void
    {
        if (!is_dir($this->output)) {
            mkdir($this->output, 0755, true);
            return;
        }
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->output, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }

    private function loadPlugins(): void
    {
        foreach ($this->config['plugins'] ?? [] as $pluginPath) {
            $this->plugins->loadFile($this->root . '/' . $pluginPath);
        }
    }

    private function runHooks(string $event, array $args): void
    {
        $this->plugins->fire($event, $args);
    }

    private function runOnPage(Page $page): Page
    {
        return $this->plugins->transformPage($page);
    }
}
