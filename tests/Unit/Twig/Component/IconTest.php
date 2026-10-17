<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\IconSet;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Icon;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class IconTest extends TestCase
{
    #[DataProvider('provideGetInternalIconData')]
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

    #[DataProvider('provideGetFontAwesomeIconData')]
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
        // fontAwesome icons using legacy icon names
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

    #[DataProvider('provideGetCustomIconData')]
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

    #[DataProvider('provideIconSetCheckersData')]
    public function testIconSetCheckers(string $appIconSet, bool $isBuiltIn, bool $isFontAwesome): void
    {
        $iconComponent = new Icon($this->getAdminContextProvider($appIconSet));

        $this->assertSame($isBuiltIn, $iconComponent->isBuiltInIconSet());
        $this->assertSame($isFontAwesome, $iconComponent->isFontAwesomeIconSet());
    }

    public static function provideIconSetCheckersData(): iterable
    {
        yield [IconSet::FontAwesome, true, true];
        yield [IconSet::Internal, true, false];
        yield [IconSet::Custom, false, false];
    }

    public function testNullIconName(): void
    {
        $iconComponent = new Icon($this->getAdminContextProvider(IconSet::FontAwesome));
        $iconDto = $iconComponent->getIcon();

        $this->assertSame('', $iconDto->getName());
        $this->assertNull($iconDto->getPath());
        $this->assertNull($iconDto->getSvgContents());
    }

    public function testUnknownInternalIcon(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/The icon "internal:this-does-not-exist" does not exist\. Check the icon name spelling and make sure that the "this-does-not-exist\.svg" file exists in the "assets\/icons\/internal\/" directory of EasyAdmin\./');

        $iconComponent = new Icon($this->getAdminContextProvider(IconSet::Internal));
        $iconComponent->name = 'internal:this-does-not-exist';
        $iconComponent->getIcon();
    }

    #[DataProvider('providePathTraversalInternalIconNames')]
    public function testInternalIconNameRejectsPathTraversalCharacters(string $iconName): void
    {
        $this->expectException(\RuntimeException::class);

        $iconComponent = new Icon($this->getAdminContextProvider(IconSet::Internal));
        $iconComponent->name = $iconName;
        $iconComponent->getIcon();
    }

    public static function providePathTraversalInternalIconNames(): iterable
    {
        yield 'parent-directory traversal' => ['internal:../../../../../tmp/secret'];
        yield 'leading slash' => ['internal:/etc/passwd'];
        yield 'plain dot-dot' => ['internal:..'];
        yield 'subdirectory access' => ['internal:subdir/icon'];
        yield 'backslash separator' => ['internal:..\\foo'];
        yield 'empty icon name' => ['internal:'];
        yield 'filetypes parent-directory traversal' => ['filetypes:../../../../../tmp/secret'];
        yield 'filetypes leading slash' => ['filetypes:/etc/passwd'];
        yield 'filetypes plain dot-dot' => ['filetypes:..'];
        yield 'filetypes subdirectory access' => ['filetypes:subdir/icon'];
        yield 'filetypes backslash separator' => ['filetypes:..\\foo'];
        yield 'filetypes empty icon name' => ['filetypes:'];
    }

    public function testDefaultCssClassWithoutSize(): void
    {
        $iconComponent = new Icon($this->getAdminContextProvider(IconSet::FontAwesome));
        $iconComponent->mount();

        $this->assertNull($iconComponent->size);
        $this->assertSame('icon', $iconComponent->getDefaultCssClass());
    }

    #[DataProvider('provideSizeData')]
    public function testDefaultCssClassWithSize(string|Size|null $size, ?Size $expectedSize, string $expectedCssClass): void
    {
        $iconComponent = new Icon($this->getAdminContextProvider(IconSet::FontAwesome));
        $iconComponent->mount(size: $size);

        $this->assertSame($expectedSize, $iconComponent->size);
        $this->assertSame($expectedCssClass, $iconComponent->getDefaultCssClass());
    }

    public static function provideSizeData(): iterable
    {
        yield 'null' => [null, null, 'icon'];
        yield 'sm as string' => ['sm', Size::Small, 'icon ea-icon-sm'];
        yield 'md as string' => ['md', Size::Medium, 'icon ea-icon-md'];
        yield 'lg as string' => ['lg', Size::Large, 'icon ea-icon-lg'];
        yield 'sm as enum' => [Size::Small, Size::Small, 'icon ea-icon-sm'];
        yield 'md as enum' => [Size::Medium, Size::Medium, 'icon ea-icon-md'];
        yield 'lg as enum' => [Size::Large, Size::Large, 'icon ea-icon-lg'];
        yield 'unknown string' => ['xl', null, 'icon'];
        yield 'empty string' => ['', null, 'icon'];
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
