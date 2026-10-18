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

    public function testDashboardsWithoutThemeEmitNoStyleOverrides(): void
    {
        $crawler = $this->client->request('GET', '/customization_title_admin');
        $headHtmlContents = $crawler->filter('head')->html();

        static::assertStringNotContainsString(':root, .ea-dark-scheme', $headHtmlContents);
        static::assertStringNotContainsString('--ea-primary', $headHtmlContents);
    }
}
