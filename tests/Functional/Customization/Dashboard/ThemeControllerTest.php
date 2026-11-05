<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Dashboard\ThemeTestDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

class ThemeControllerTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return ThemeTestDashboardController::class;
    }

    public function testThemeStyleTagIsEmitted(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $headHtmlContents = $crawler->filter('head')->html();

        static::assertStringContainsString(":root, .ea-dark-scheme {\n", $headHtmlContents);
        static::assertStringContainsString('--ea-primary: #15803d;', $headHtmlContents);
        static::assertStringContainsString('--ea-primary-foreground: #fff;', $headHtmlContents);
        static::assertStringContainsString('--ea-radius: 0.375rem;', $headHtmlContents);
        static::assertStringContainsString('--ea-spacing: 5px;', $headHtmlContents);
        static::assertStringContainsString('--gray-50: var(--neutral-gray-50);', $headHtmlContents);
        static::assertStringContainsString('--gray-950: var(--neutral-gray-950);', $headHtmlContents);

        // the dark-only overrides are emitted in a separate block after the common one
        $commonBlockPosition = strpos($headHtmlContents, ":root, .ea-dark-scheme {\n");
        $darkBlockPosition = strpos($headHtmlContents, "\n.ea-dark-scheme {\n");
        static::assertNotFalse($darkBlockPosition);
        static::assertGreaterThan($commonBlockPosition, $darkBlockPosition);
        static::assertStringContainsString('--ea-primary: oklch(0.6 0.2 150);', $headHtmlContents);
        static::assertStringContainsString('--ea-primary-foreground: #000;', $headHtmlContents);

        $darkGrayPosition = strpos($headHtmlContents, '--gray-50: var(--warm-gray-50);');
        static::assertNotFalse($darkGrayPosition);
        static::assertGreaterThan($darkBlockPosition, $darkGrayPosition);
        static::assertStringContainsString('--gray-950: var(--warm-gray-950);', $headHtmlContents);
    }

    public function testThemeStyleTagIsEmittedBetweenAppCssAndUserCss(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $headHtmlContents = $crawler->filter('head')->html();

        static::assertSame(1, preg_match('/<link rel="stylesheet" href="[^"]*app\.[^"]*css"/', $headHtmlContents, $appCssMatches, \PREG_OFFSET_CAPTURE));
        $appCssPosition = $appCssMatches[0][1];
        $themeStylePosition = strpos($headHtmlContents, ':root, .ea-dark-scheme');
        $userCssPosition = strpos($headHtmlContents, 'user-styles.css');

        static::assertNotFalse($themeStylePosition);
        static::assertNotFalse($userCssPosition);
        static::assertGreaterThan($appCssPosition, $themeStylePosition);
        static::assertGreaterThan($themeStylePosition, $userCssPosition);
    }

    public function testBundledFonts(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $headHtmlContents = $crawler->filter('head')->html();

        $fontPreloads = $crawler->filter('head link[rel="preload"][as="font"]');
        static::assertCount(1, $fontPreloads);
        static::assertMatchesRegularExpression('#/bundles/easyadmin/fonts/inter-latin-wght-normal\.[0-9a-f]+\.woff2$#', $fontPreloads->attr('href'));
        static::assertSame('font/woff2', $fontPreloads->attr('type'));
        static::assertSame('', $fontPreloads->attr('crossorigin'));

        static::assertStringContainsString('--font-family-sans-serif: "Inter Variable", "Inter Variable Fallback", var(--font-family-system-sans-serif);', $headHtmlContents);
        static::assertStringContainsString('--font-weight-normal: 435;', $headHtmlContents);
        static::assertStringContainsString('--font-family-monospace: "JetBrains Mono Variable", "JetBrains Mono Variable Fallback", var(--font-family-system-monospace);', $headHtmlContents);

        static::assertCount(0, $crawler->filter('head link[rel="preconnect"]'));
        static::assertStringNotContainsString('fonts.bunny.net', $headHtmlContents);
    }

    public function testBundledFontFacesAreDefinedPerSubset(): void
    {
        $manifest = json_decode((string) file_get_contents(__DIR__.'/../../../../public/manifest.json'), true, flags: \JSON_THROW_ON_ERROR);
        $appCssContents = (string) file_get_contents(__DIR__.'/../../../../public/'.$manifest['app.css']);

        static::assertSame(6, preg_match_all('/@font-face\{[^}]*font-family:"?IBM Plex Sans Variable"?;[^}]*unicode-range:[^}]+\}/i', $appCssContents));
        foreach (['cyrillic-ext', 'cyrillic', 'greek', 'vietnamese', 'latin-ext', 'latin'] as $subset) {
            static::assertStringContainsString(sprintf('fonts/ibm-plex-sans-%s-wght-normal.', $subset), $appCssContents);
        }

        // when ranges overlap, browsers use the last declared face, so 'latin' must be the last one
        $latinPosition = strpos($appCssContents, 'fonts/ibm-plex-sans-latin-wght-normal.');
        $vietnamesePosition = strpos($appCssContents, 'fonts/ibm-plex-sans-vietnamese-wght-normal.');
        static::assertGreaterThan($vietnamesePosition, $latinPosition);
    }

    public function testRemoteFonts(): void
    {
        $crawler = $this->client->request('GET', '/customization_remote_font_admin');
        $headHtmlContents = $crawler->filter('head')->html();

        static::assertSame('https://fonts.bunny.net', $crawler->filter('head link[rel="preconnect"]')->attr('href'));
        static::assertSame('https://fonts.bunny.net/css2?family=Lexend:wght@400;500;600&family=Fira+Code:wght@400;500;600&display=swap', $crawler->filter('head link[rel="stylesheet"][href^="https://fonts.bunny.net"]')->attr('href'));
        static::assertCount(0, $crawler->filter('head link[rel="preload"][as="font"]'));
        static::assertStringContainsString('--font-family-sans-serif: "Lexend", var(--font-family-system-sans-serif);', $headHtmlContents);
        static::assertStringContainsString('--font-weight-normal: 400;', $headHtmlContents);
        // the quotes of the feature tags must not be escaped inside the <style> element
        static::assertStringContainsString('--font-feature-settings: "tnum" 1, "ss01" 1;', $headHtmlContents);
        static::assertStringContainsString('--font-family-monospace: "Fira Code", var(--font-family-system-monospace);', $headHtmlContents);
    }

    public function testDashboardsWithoutThemeUseTheDefaultFont(): void
    {
        $crawler = $this->client->request('GET', '/customization_title_admin');
        $headHtmlContents = $crawler->filter('head')->html();

        $fontPreloads = $crawler->filter('head link[rel="preload"][as="font"]');
        static::assertCount(1, $fontPreloads);
        static::assertMatchesRegularExpression('#/bundles/easyadmin/fonts/inter-latin-wght-normal\.[0-9a-f]+\.woff2$#', $fontPreloads->attr('href'));
        static::assertCount(0, $crawler->filter('head link[rel="preconnect"]'));
        static::assertStringNotContainsString('--font-family', $headHtmlContents);
    }

    public function testDashboardsWithoutThemeEmitNoStyleOverrides(): void
    {
        $crawler = $this->client->request('GET', '/customization_title_admin');
        $headHtmlContents = $crawler->filter('head')->html();

        static::assertStringNotContainsString(':root, .ea-dark-scheme', $headHtmlContents);
        static::assertStringNotContainsString('--ea-primary', $headHtmlContents);
    }
}
