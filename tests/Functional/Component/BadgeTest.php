<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;

/**
 * Rendering tests for the <twig:ea:Badge> component.
 */
class BadgeTest extends AbstractFieldFunctionalTest
{
    private function renderBadge(string $template, array $context = []): string
    {
        return static::getContainer()->get('twig')->createTemplate($template)->render($context);
    }

    public function testDefaultColorAndVariant(): void
    {
        self::assertSame(
            '<span class="badge badge-secondary">Hello</span>',
            $this->renderBadge('<twig:ea:Badge>Hello</twig:ea:Badge>')
        );
    }

    public function testNamedColor(): void
    {
        self::assertSame(
            '<span class="badge badge-success">OK</span>',
            $this->renderBadge('<twig:ea:Badge color="success">OK</twig:ea:Badge>')
        );
    }

    public function testUnknownColorIsRenderedAsCustomColor(): void
    {
        self::assertSame(
            '<span class="badge badge-currency">EUR</span>',
            $this->renderBadge('<twig:ea:Badge color="currency">EUR</twig:ea:Badge>')
        );
    }

    public function testEmptyColorRendersNoColorClass(): void
    {
        self::assertSame(
            '<span class="badge">x</span>',
            $this->renderBadge('<twig:ea:Badge color="">x</twig:ea:Badge>')
        );
    }

    public function testSmallSize(): void
    {
        self::assertSame(
            '<span class="badge badge-secondary badge-sm">x</span>',
            $this->renderBadge('<twig:ea:Badge size="sm">x</twig:ea:Badge>')
        );
    }

    public function testSmallSizeAsEnum(): void
    {
        self::assertSame(
            '<span class="badge badge-secondary badge-sm">x</span>',
            $this->renderBadge('<twig:ea:Badge size="{{ size }}">x</twig:ea:Badge>', ['size' => Size::Small])
        );
    }

    public function testRadiusFull(): void
    {
        self::assertSame(
            '<span class="badge badge-secondary ea-rounded-full">x</span>',
            $this->renderBadge('<twig:ea:Badge radius="full">x</twig:ea:Badge>')
        );
    }

    public function testNoRadiusClassByDefault(): void
    {
        self::assertStringNotContainsString('ea-rounded', $this->renderBadge('<twig:ea:Badge>x</twig:ea:Badge>'));
    }

    public function testExtraAttributesAreMerged(): void
    {
        self::assertSame(
            '<span class="badge badge-info" title="hello">x</span>',
            $this->renderBadge('<twig:ea:Badge color="info" title="hello">x</twig:ea:Badge>')
        );
    }

    public function testCustomClassIsMergedWithDefaults(): void
    {
        self::assertSame(
            '<span class="badge badge-info my-badge">x</span>',
            $this->renderBadge('<twig:ea:Badge color="info" class="my-badge">x</twig:ea:Badge>')
        );
    }

    public function testLeadingIconIsRenderedBeforeLabel(): void
    {
        $html = $this->renderBadge('<twig:ea:Badge color="info" icon="fa-star">Label</twig:ea:Badge>');

        self::assertStringContainsString('class="icon"', $html);
        self::assertLessThan(strpos($html, 'Label'), strpos($html, 'class="icon"'));
    }

    public function testTrailingIconIsRenderedAfterLabel(): void
    {
        $html = $this->renderBadge('<twig:ea:Badge color="info" endIcon="fa-star">Label</twig:ea:Badge>');

        self::assertStringContainsString('class="icon"', $html);
        self::assertGreaterThan(strpos($html, 'Label'), strpos($html, 'class="icon"'));
    }

    public function testVariant(): void
    {
        self::assertSame(
            '<span class="badge badge-danger badge-variant-solid">x</span>',
            $this->renderBadge('<twig:ea:Badge color="danger" variant="solid">x</twig:ea:Badge>')
        );
    }

    public function testDotVariantRendersTheDotBeforeTheLabel(): void
    {
        self::assertSame(
            '<span class="badge badge-success badge-variant-dot"><span class="badge-dot" aria-hidden="true"></span>Online</span>',
            $this->renderBadge('<twig:ea:Badge color="success" variant="dot">Online</twig:ea:Badge>')
        );
    }

    public function testDismissButtonIsRenderedAfterTheLabel(): void
    {
        $html = $this->renderBadge('<twig:ea:Badge dismissible endIcon="fa-star">Label</twig:ea:Badge>');

        self::assertStringContainsString('<button type="button" class="badge-dismiss" aria-label="Remove the item">', $html);
        self::assertGreaterThan(strpos($html, 'class="icon"'), strpos($html, 'class="badge-dismiss"'));
    }

    public function testNoDismissButtonByDefault(): void
    {
        self::assertStringNotContainsString('badge-dismiss', $this->renderBadge('<twig:ea:Badge>x</twig:ea:Badge>'));
    }

    public function testLargeSize(): void
    {
        self::assertSame(
            '<span class="badge badge-secondary badge-lg">x</span>',
            $this->renderBadge('<twig:ea:Badge size="lg">x</twig:ea:Badge>')
        );
    }

    public function testStyleAttributeIsRenderedAsHtmlAttribute(): void
    {
        self::assertSame(
            '<span class="badge" style="background: red">x</span>',
            $this->renderBadge('<twig:ea:Badge color="" style="background: red">x</twig:ea:Badge>')
        );
    }

    /**
     * @group legacy
     */
    public function testColorInVariantStillWorks(): void
    {
        self::assertSame(
            '<span class="badge badge-success">OK</span>',
            $this->renderBadge('<twig:ea:Badge variant="success">OK</twig:ea:Badge>')
        );
    }
}
