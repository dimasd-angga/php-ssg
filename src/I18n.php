<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Locale resolution for multilingual sites.
 *
 * Content is organized under locale-prefixed directories:
 *   content/
 *     en/index.md       → /en/
 *     en/blog/hello.md  → /en/blog/hello/
 *     fr/index.md       → /fr/
 *
 * The default locale (configured in ssg.config.php → site.locale) can be
 * served at the root by setting site.defaultLocaleAtRoot = true:
 *   content/en/index.md → /          (when en is the default)
 *
 * Pages outside any locale directory are treated as locale-less and served
 * at their unmodified slug (handy for shared 404 pages, robots.txt, etc.).
 */
class I18n
{
    /** @var string[] */
    public array $locales;
    public string $defaultLocale;
    public bool $defaultAtRoot;

    public function __construct(array $config)
    {
        $this->locales = $config['locales'] ?? [];
        $this->defaultLocale = $config['locale'] ?? 'en';
        $this->defaultAtRoot = (bool) ($config['defaultLocaleAtRoot'] ?? true);
    }

    /**
     * Given a content slug like "en/blog/hello", returns
     *   ['locale' => 'en', 'slug' => 'blog/hello'] if 'en' is a known locale,
     * otherwise   ['locale' => null, 'slug' => 'en/blog/hello'].
     */
    public function split(string $slug): array
    {
        $parts = explode('/', $slug, 2);
        $maybeLocale = $parts[0];

        if (in_array($maybeLocale, $this->locales, true)) {
            return [
                'locale' => $maybeLocale,
                'slug' => $parts[1] ?? '',
            ];
        }
        return ['locale' => null, 'slug' => $slug];
    }

    public function urlPrefix(?string $locale): string
    {
        if ($locale === null) return '';
        if ($this->defaultAtRoot && $locale === $this->defaultLocale) return '';
        return '/' . $locale;
    }

    /**
     * Given a page in one locale, build the URL for the equivalent page in
     * another locale (assuming it exists). Used by templates to render
     * <a href="..."> language switchers.
     */
    public function alternateUrl(string $slug, string $fromLocale, string $toLocale): string
    {
        $relative = $this->stripLocale($slug, $fromLocale);
        $prefix = $this->urlPrefix($toLocale);
        if ($relative === '' || $relative === 'index') {
            return $prefix === '' ? '/' : $prefix . '/';
        }
        return $prefix . '/' . $relative . '/';
    }

    private function stripLocale(string $slug, string $locale): string
    {
        if (str_starts_with($slug, $locale . '/')) {
            return substr($slug, strlen($locale) + 1);
        }
        return $slug;
    }
}
