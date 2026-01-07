<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\IconSet;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Icon;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class IconTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @dataProvider provideGetInternalIconData
     */
    public function testGetInternalIcon(string $iconName, string $appIconSet): void
    {
        $iconComponent = new Icon($this->getAdminContextProvider($appIconSet));
        $iconComponent->name = $iconName;
        $iconDto = $iconComponent->getIcon();

        $this->assertSame('internal:user', $iconDto->getName());
        $this->assertStringEndsWith('assets/icons/internal/user.svg', $iconDto->getPath());
        $this->assertStringContainsString('(Icons: CC BY 4.0, Fonts: SIL OFL 1.1, Code: MIT License) Copyright 2024 Fonticons, Inc.', $iconDto->getSvgContents());
    }

    public static function provideGetInternalIconData(): iterable
    {
        // internal icons used in EasyAdmin UI; we test it with different icon sets to
        // test that the icon set is ignored for internal icons and the result is always the same
        yield ['internal:user', IconSet::Internal];
        yield ['internal:user', IconSet::Custom];
        yield ['internal:user', IconSet::FontAwesome];
    }

    /**
     * @dataProvider provideGetFontAwesomeIconData
     *
     * @group legacy (needed for tests that use legacy FontAwesome icon names)
     */
    public function testGetFontAwesomeIcon(string $iconName): void
    {
        $iconComponent = new Icon($this->getAdminContextProvider(IconSet::FontAwesome));
        $iconComponent->name = $iconName;
        $iconDto = $iconComponent->getIcon();

        $this->assertSame($iconName, $iconDto->getName());
        $this->assertNull($iconDto->getPath());
        $this->assertNull($iconDto->getSvgContents());
    }

    public static function provideGetFontAwesomeIconData(): iterable
    {
        yield ['fa fa-list'];
        yield ['fa-solid fa-list'];
        yield ['fa-list fa-solid'];
        yield ['fa-list fa-solid fa-fw'];
        yield ['fa-list fa-fw fa-solid'];
        yield ['fa-brands fa-twitter'];
        yield ['fa-twitter fa-brands'];
        yield ['fa-twitter fa-brands fa-fw'];
        yield ['fa-twitter fa-fw fa-brands'];
        yield ['fa-clock fa-regular'];
        yield ['fa-regular fa-clock'];
        yield ['fa-regular fa-clock fa-fw'];
        yield ['fa-regular fa-fw fa-clock'];
        yield ['fa-address-card'];
        yield ['fas fa-address-card'];
        yield ['fa-address-card fas'];
        yield ['fa-address-card fas fa-fw'];
        yield ['fa-address-card fa-fw fas'];
        yield ['fas fa-fw fa-address-card'];
        yield ['fas fa-address-card fa-fw'];
        // FontAwesome icons using legacy icon names
        yield ['fa-file-text-o'];
        yield ['fa fa-file-text-o'];
        yield ['far fa-file-text-o'];
        yield ['fas fa-file-text-o'];
        yield ['fa-file-text-o fa'];
        yield ['fa-file-text-o fas'];
        yield ['fa-file-text-o far'];
        yield ['fa-fw fa-file-text-o fa'];
        yield ['fa-fw fa-file-text-o fas'];
        yield ['fa-fw fa-file-text-o far'];
    }

    /**
     * @dataProvider provideGetCustomIconData
     */
    public function testGetCustomIcon(string $iconName): void
    {
        $iconComponent = new Icon($this->getAdminContextProvider(IconSet::Custom));
        $iconComponent->name = $iconName;
        $iconDto = $iconComponent->getIcon();

        $this->assertSame($iconName, $iconDto->getName());
        $this->assertNull($iconDto->getPath());
        $this->assertNull($iconDto->getSvgContents());
    }

    public static function provideGetCustomIconData(): iterable
    {
        yield ['custom:my-icon'];
        yield ['another-custom-prefix:some-other-icon'];
    }

    public function testUnknownInternalIcon(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The icon "internal:this-does-not-exist" does not exist\. Check the icon name spelling and make sure that the "this-does-not-exist\.svg" file exists in the "assets\/icons\/internal\/ directory of EasyAdmin"\./');

        $iconComponent = new Icon($this->getAdminContextProvider(IconSet::Internal));
        $iconComponent->name = 'internal:this-does-not-exist';
        $iconComponent->getIcon();
    }

    private function getAdminContextProvider(string $appIconSet): AdminContextProvider
    {
        $assetsDto = new AssetsDto();
        $assetsDto->setIconSet($appIconSet);
        $assetsDto->setDefaultIconPrefix('');

        $adminContext = AdminContext::forTesting(
            dashboardContext: DashboardContext::forTesting(assets: $assetsDto),
        );

        $request = new Request(attributes: [EA::CONTEXT_REQUEST_ATTRIBUTE => $adminContext]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new AdminContextProvider($requestStack);
    }
}
