<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Kernel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

/**
 * Renders the ea:Flag component through the real Twig + TwigComponent stack
 * (no mocks) to test the behavior defined in its template.
 */
class FlagComponentTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testRendersFlagSvgInsideWrapper(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', ['countryCode' => 'ES']);

        $this->assertStringContainsString('<span class="country-flag-wrapper">', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('class="country-flag"', $html);
        $this->assertStringContainsString('height="17"', $html);
    }

    public function testHtmlAttributesAreAppliedToTheWrapper(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', [
            'countryCode' => 'ES',
            'class' => 'rounded shadow-sm',
            'title' => 'Shipping destination',
            'data-country' => 'ES',
        ]);

        $this->assertStringContainsString('class="country-flag-wrapper rounded shadow-sm"', $html);
        $this->assertStringContainsString('title="Shipping destination"', $html);
        $this->assertStringContainsString('data-country="ES"', $html);
    }

    public function testShowNameRendersTheCountryNameNextToTheFlag(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', [
            'countryCode' => 'ES',
            'countryName' => 'Spain',
            'showName' => true,
        ]);

        $this->assertStringContainsString('class="country-flag-wrapper country-flag-with-name"', $html);
        $this->assertStringContainsString('</svg><span class="country-flag-name" style="margin-inline-start: var(--flag-name-gap, 0.375em)">Spain</span></span>', $html);
    }

    #[DataProvider('provideLocalizedCountryNames')]
    public function testLocalizedCountryNamesAreRendered(string $countryCode, string $localizedName): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', [
            'countryCode' => $countryCode,
            'countryName' => $localizedName,
            'showName' => true,
        ]);

        $this->assertStringContainsString(sprintf('<title>%s</title>', $localizedName), $html);
        $this->assertStringContainsString(sprintf('<span class="country-flag-name" style="margin-inline-start: var(--flag-name-gap, 0.375em)">%s</span>', $localizedName), $html);
    }

    public static function provideLocalizedCountryNames(): iterable
    {
        yield 'Spanish' => ['ES', 'España'];
        yield 'Ukrainian' => ['UA', 'Україна'];
        yield 'Ukrainian name of another country' => ['ES', 'Іспанія'];
    }

    public function testNameIsNotRenderedByDefault(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', [
            'countryCode' => 'ES',
            'countryName' => 'Spain',
        ]);

        $this->assertStringContainsString('</svg></span>', $html);
        $this->assertStringNotContainsString('country-flag-with-name', $html);
        $this->assertStringNotContainsString('country-flag-name', $html);
    }

    public function testFlagIsRoundedAndOutlinedByDefault(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', ['countryCode' => 'JP']);

        $this->assertStringContainsString('class="country-flag" style="border-radius: var(--flag-radius, 2px); outline: 1px solid var(--flag-outline-color, color-mix(in srgb, currentColor 20%, transparent)); outline-offset: var(--flag-outline-offset, -1px)"', $html);
    }

    public function testRadiusAndBorderedProps(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', ['countryCode' => 'JP', 'radius' => 'lg', 'bordered' => false]);

        $this->assertStringContainsString('class="country-flag" style="border-radius: var(--border-radius-lg, 8px)"', $html);
    }

    public function testRawFlagHasNoStyles(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', ['countryCode' => 'JP', 'radius' => 'none', 'bordered' => false]);

        $this->assertStringContainsString('class="country-flag" height="17"', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function testStyleAttributeIsAppliedToTheWrapper(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', ['countryCode' => 'JP', 'style' => '--flag-radius: 6px']);

        $this->assertStringContainsString('<span class="country-flag-wrapper" style="--flag-radius: 6px">', $html);
    }

    public function testUnknownCountryRendersTheDefaultFallback(): void
    {
        $html = (string) $this->renderTwigComponent('ea:Flag', ['countryCode' => 'ZZ']);

        $this->assertStringContainsString('class="country-flag-wrapper"', $html);
        $this->assertStringContainsString('You are not seeing a country flag here', $html);
        $this->assertStringContainsString('fill="#ff0000"', $html);
    }

    public function testFallbackBlockReplacesTheDefaultFallback(): void
    {
        $html = (string) $this->renderTwigComponent(
            'ea:Flag',
            ['countryCode' => 'ZZ'],
            blocks: ['fallback' => '<span class="unknown-flag">?</span>']
        );

        $this->assertStringContainsString('<span class="unknown-flag">?</span>', $html);
        $this->assertStringNotContainsString('You are not seeing a country flag here', $html);
    }

    public function testFallbackBlockIsIgnoredWhenTheFlagExists(): void
    {
        $html = (string) $this->renderTwigComponent(
            'ea:Flag',
            ['countryCode' => 'ES'],
            blocks: ['fallback' => '<span class="unknown-flag">?</span>']
        );

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('unknown-flag', $html);
    }
}
