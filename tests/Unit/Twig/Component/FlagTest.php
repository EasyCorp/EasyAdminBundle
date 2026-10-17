<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Flag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FlagTest extends TestCase
{
    public function testKnownCountryCodeRendersShippedSvg(): void
    {
        $flag = new Flag();
        $flag->countryCode = 'ES';

        $svg = $flag->getFlagAsSvg();

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringNotContainsString('You are not seeing a country flag here', $svg);
    }

    public function testUnknownCountryCodeRendersFallbackWithEscapedValue(): void
    {
        $flag = new Flag();
        $flag->countryCode = 'XX';

        $svg = $flag->getFlagAsSvg();

        $this->assertStringContainsString('You are not seeing a country flag here', $svg);
        $this->assertStringContainsString('"XX.svg"', $svg);
    }

    public function testPathTraversalPayloadDoesNotLeakAnyFile(): void
    {
        // Plant a .svg file at a path that the unpatched component could have
        // reached from src/Twig/Component/../../../assets/icons/flags/<payload>.svg
        // via `..` segments. The filename and the file contents use *different*
        // markers: the filename marker may legitimately appear in the fallback
        // SVG (because it echoes the country code), but the contents marker
        // must never appear unless file_get_contents() was actually called.
        $filenameMarker = bin2hex(random_bytes(6));
        $contentsMarker = 'EA_TEST_LEAKED_CONTENTS_'.bin2hex(random_bytes(6));
        $secretPath = sys_get_temp_dir().'/ea_flag_'.$filenameMarker.'.svg';
        file_put_contents($secretPath, '<svg>'.$contentsMarker.'</svg>');

        $relativeTraversal = str_repeat('../', 10).ltrim(substr($secretPath, 0, -4), '/');

        try {
            $flag = new Flag();
            $flag->countryCode = $relativeTraversal;

            $svg = $flag->getFlagAsSvg();
        } finally {
            @unlink($secretPath);
        }

        $this->assertStringNotContainsString($contentsMarker, $svg, 'Path traversal payload reached file_get_contents() and leaked file contents.');
        $this->assertStringContainsString('You are not seeing a country flag here', $svg);
    }

    #[DataProvider('provideXssPayloads')]
    public function testXssPayloadIsEscapedInFallback(string $payload, string $mustNotAppear): void
    {
        $flag = new Flag();
        $flag->countryCode = $payload;

        $svg = $flag->getFlagAsSvg();

        $this->assertStringContainsString('You are not seeing a country flag here', $svg);
        $this->assertStringNotContainsString($mustNotAppear, $svg);
    }

    public static function provideXssPayloads(): iterable
    {
        yield 'script tag' => [
            '"><script>alert(1)</script>',
            '<script>alert(1)</script>',
        ];
        yield 'title-break + script' => [
            '"></title><script>alert(1)</script>',
            '</title><script>alert(1)</script>',
        ];
        yield 'attribute break' => [
            '" onload="alert(1)',
            '" onload="alert(1)',
        ];
    }

    public function testCountryNamePropOverridesIntlName(): void
    {
        $flag = new Flag();
        $flag->countryCode = 'ES';
        $flag->countryName = 'Custom Name';

        $this->assertSame('Custom Name', $flag->getCountryName());
        $this->assertStringContainsString('<title>Custom Name</title>', $flag->getFlagAsSvg());
    }

    public function testCountryNameIsLocalizedUsingTheDefaultLocale(): void
    {
        $previousLocale = \Locale::getDefault();

        try {
            $flag = new Flag();
            $flag->countryCode = 'ES';

            \Locale::setDefault('es');
            $this->assertSame('España', $flag->getCountryName());
            $this->assertStringContainsString('<title>España</title>', $flag->getFlagAsSvg());

            \Locale::setDefault('uk');
            $this->assertSame('Іспанія', $flag->getCountryName());
            $this->assertStringContainsString('<title>Іспанія</title>', $flag->getFlagAsSvg());
        } finally {
            \Locale::setDefault($previousLocale);
        }
    }

    #[DataProvider('provideXssPayloads')]
    public function testXssPayloadInCountryNameIsEscapedInSvg(string $payload, string $mustNotAppear): void
    {
        $flag = new Flag();
        $flag->countryCode = 'ES';
        $flag->countryName = $payload;

        $this->assertStringNotContainsString($mustNotAppear, $flag->getFlagAsSvg());
    }

    public function testFlagExists(): void
    {
        $flag = new Flag();
        $flag->countryCode = 'ES';
        $this->assertTrue($flag->flagExists());

        $flag = new Flag();
        $flag->countryCode = 'XX';
        $this->assertFalse($flag->flagExists());

        $flag = new Flag();
        $flag->countryCode = '../../../etc/passwd';
        $this->assertFalse($flag->flagExists());
    }

    public function testFallbackSvgIsPubliclyAvailableAndEscaped(): void
    {
        $flag = new Flag();
        $flag->countryCode = '"><script>alert(1)</script>';

        $svg = $flag->getFallbackSvg();

        $this->assertStringContainsString('You are not seeing a country flag here', $svg);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $svg);
    }
}
