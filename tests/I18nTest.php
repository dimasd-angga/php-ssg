<?php

declare(strict_types=1);

namespace PhpSsg\Tests;

use PhpSsg\I18n;
use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase
{
    public function testSplitsLocaleFromSlug(): void
    {
        $i = new I18n(['locales' => ['en', 'fr'], 'locale' => 'en']);
        $this->assertSame(['locale' => 'en', 'slug' => 'blog/hello'], $i->split('en/blog/hello'));
        $this->assertSame(['locale' => 'fr', 'slug' => 'index'], $i->split('fr/index'));
    }

    public function testReturnsNullLocaleForUnknownPrefix(): void
    {
        $i = new I18n(['locales' => ['en'], 'locale' => 'en']);
        $this->assertSame(['locale' => null, 'slug' => 'about'], $i->split('about'));
    }

    public function testDefaultLocaleAtRootGetsNoPrefix(): void
    {
        $i = new I18n(['locales' => ['en', 'fr'], 'locale' => 'en', 'defaultLocaleAtRoot' => true]);
        $this->assertSame('', $i->urlPrefix('en'));
        $this->assertSame('/fr', $i->urlPrefix('fr'));
    }

    public function testDefaultLocaleAtRootDisabled(): void
    {
        $i = new I18n(['locales' => ['en', 'fr'], 'locale' => 'en', 'defaultLocaleAtRoot' => false]);
        $this->assertSame('/en', $i->urlPrefix('en'));
    }

    public function testAlternateUrlForLanguageSwitch(): void
    {
        $i = new I18n(['locales' => ['en', 'fr'], 'locale' => 'en']);
        $this->assertSame('/fr/blog/hello/', $i->alternateUrl('en/blog/hello', 'en', 'fr'));
        $this->assertSame('/blog/hello/', $i->alternateUrl('fr/blog/hello', 'fr', 'en'));
        $this->assertSame('/', $i->alternateUrl('en/index', 'en', 'en'));
    }
}
