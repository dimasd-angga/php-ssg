# Changelog

All notable changes to this project will be documented in this file.

## 0.2.0 — 2024-12-27

### Added
- Plugin hook system: `beforeBuild`, `onPage`, `afterBuild`
- Reading-time plugin example (`examples/plugins/reading-time.php`)
- Tag taxonomy + auto-generated tag archive pages
- `sitemap.xml` and per-collection `feed.xml` (RSS 2.0) generation
- `--drafts` flag on `build` and `watch`

### Fixed
- `onPage` plugin chain now correctly passes mutated Page to subsequent callbacks
- Watch mode no longer misses files in nested directories created mid-watch

## 0.1.0 — 2024-09-27

- Initial release: Markdown + frontmatter, native PHP templates, asset pipeline,
  collections, pagination, CLI (`new`, `build`, `serve`, `watch`, `clean`,
  `validate`, `post`, `page`).

