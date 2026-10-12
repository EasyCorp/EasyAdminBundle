<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;

/**
 * Rendering tests for the <twig:ea:Icon> component.
 */
class IconTest extends AbstractFieldFunctionalTest
{
    public function testWithoutSize(): void
    {
        $html = $this->renderIcon('<twig:ea:Icon name="fa-solid fa-user"/>');

        $this->assertWrapperCssClass('icon', $html);
        self::assertStringContainsString('<i class="fa-solid fa-user"></i>', $html);
    }

    public function testSize(): void
    {
        $this->assertWrapperCssClass('icon ea-icon-lg', $this->renderIcon('<twig:ea:Icon name="fa-solid fa-user" size="lg"/>'));
    }

    public function testSizeAsEnum(): void
    {
        $this->assertWrapperCssClass('icon ea-icon-sm', $this->renderIcon('<twig:ea:Icon name="fa-solid fa-user" size="{{ size }}"/>', ['size' => Size::Small]));
    }

    public function testUnknownSizeRendersNoSizeClass(): void
    {
        $this->assertWrapperCssClass('icon', $this->renderIcon('<twig:ea:Icon name="fa-solid fa-user" size="xl"/>'));
    }

    public function testSizeIsMergedWithCustomCssClass(): void
    {
        $this->assertWrapperCssClass('icon ea-icon-md text-danger', $this->renderIcon('<twig:ea:Icon name="fa-solid fa-user" size="md" class="text-danger"/>'));
    }

    public function testSizeOfInternalIcon(): void
    {
        $html = $this->renderIcon('<twig:ea:Icon name="filetypes:document" size="lg"/>');

        $this->assertWrapperCssClass('icon ea-icon-lg', $html);
        self::assertStringContainsString('<svg', $html);
    }

    public function testSizeOfDropdownItemIcon(): void
    {
        $html = $this->renderIcon('<twig:ea:ActionMenu:ActionList:Item label="Edit" url="/edit" icon="fa-solid fa-user" icon:size="lg"/>');

        self::assertMatchesRegularExpression('/<span\s+class="icon ea-icon-lg">\s*<i class="fa-solid fa-user"><\/i>/', $html);
    }

    private function renderIcon(string $template, array $context = []): string
    {
        return static::getContainer()->get('twig')->createTemplate($template)->render($context);
    }

    private function assertWrapperCssClass(string $expectedCssClass, string $html): void
    {
        self::assertMatchesRegularExpression(sprintf('/^<span\s+class="%s">/', preg_quote($expectedCssClass, '/')), $html);
    }
}
