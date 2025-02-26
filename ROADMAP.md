# Roadmap

## Near-term (v0.4)

- **Incremental builds** — only re-render pages whose content, frontmatter, or
  upstream template actually changed. Should keep watch-mode rebuilds under
  20ms even for 1000-page sites.
- **Image optimization pipeline** — auto-resize images placed in `assets/images`
  and emit WebP alternates. Templates would use `$this->image('hero.jpg', 800)`
  helpers.

## Medium-term (v0.5–0.6)

- **Admin UI** — browser-based content editor that writes back to the
  filesystem. Optional add-on, not part of the core.
- **Tailwind CSS integration** — first-class support for the standalone
  Tailwind CLI binary (no Node required) with built-in JIT mode.
- **Algolia search plugin** — bundle frontmatter and excerpts into a search
  index uploaded at build time.

## Long-term

- **WordPress importer** — read a WP export XML and emit Markdown files +
  frontmatter, including image downloads.
- **Drafts preview server** — `php-ssg serve --drafts --share` to expose a
  temporary tunnel for client review.

## Not on the roadmap

Things explicitly out of scope:

- Server-side rendering at request time (php-ssg is fully static).
- A JavaScript bundler. Bring your own; the asset pipeline doesn't process JS.
- A databased-backed content store. If you need that, use a CMS.
- A "themes" marketplace. Templates are just PHP files; copy what you like.

Got a suggestion? Open an issue with the "roadmap" label.
