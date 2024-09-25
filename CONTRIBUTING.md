# Contributing to php-ssg

Thanks for your interest. php-ssg is small and stays small — please read this before opening a PR so we don't waste each other's time.

## Philosophy

- Zero runtime dependencies. The whole point of php-ssg is "just PHP, nothing else." A PR that introduces a Composer dep needs a very strong justification.
- Small surface area. We'd rather expose a plugin hook than add a config option.
- Speed matters. Builds should stay fast. If your change adds measurable build time, mention it in the PR.

## Quick start

```bash
git clone https://github.com/dimasd-angga/php-ssg.git
cd php-ssg
composer install
vendor/bin/phpunit
```

## Running the test suite

```bash
composer test
```

Add tests for any new feature or bugfix. Tests live under `tests/`.

## Coding style

- PSR-12, but pragmatic — no need for a linter in the PR pipeline yet.
- `declare(strict_types=1);` at the top of every file in `src/`.
- Prefer plain functions and small classes over deep hierarchies.

## Filing a bug

Include:
1. PHP version (`php --version`)
2. Operating system
3. Minimal reproduction: smallest content/template that triggers the issue
4. What you expected vs. what happened

## Filing a feature request

If you're not sure whether something fits, open an issue first to discuss rather than building a large PR that gets rejected.

## Releases

Releases follow [semver](https://semver.org). Major changes are documented in `CHANGELOG.md`.
