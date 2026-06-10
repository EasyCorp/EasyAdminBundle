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

    public function testDefaultVariantIsSecondary(): void
    {
        self::assertSame(
            '<span class="badge badge-secondary">Hello</span>',
            $this->renderBadge('<twig:ea:Badge>Hello</twig:ea:Badge>')
        );
    }

    public function testNamedVariant(): void
    {
        self::assertSame(
            '<span class="badge badge-success">OK</span>',
            $this->renderBadge('<twig:ea:Badge variant="success">OK</twig:ea:Badge>')
        );
    }

    public function testUnknownVariantIsRenderedAsCustomVariant(): void
    {
        self::assertSame(
            '<span class="badge badge-currency">EUR</span>',
            $this->renderBadge('<twig:ea:Badge variant="currency">EUR</twig:ea:Badge>')
        );
    }

    public function testEmptyVariantRendersNoVariantClass(): void
    {
        self::assertSame(
            '<span class="badge">x</span>',
            $this->renderBadge('<twig:ea:Badge variant="">x</twig:ea:Badge>')
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
            $this->renderBadge('<twig:ea:Badge variant="info" title="hello">x</twig:ea:Badge>')
        );
    }

    public function testCustomClassIsMergedWithDefaults(): void
    {
        self::assertSame(
            '<span class="badge badge-info my-badge">x</span>',
            $this->renderBadge('<twig:ea:Badge variant="info" class="my-badge">x</twig:ea:Badge>')
        );
    }

    public function testLeadingIconIsRenderedBeforeLabel(): void
    {
        $html = $this->renderBadge('<twig:ea:Badge variant="info" icon="fa-star">Label</twig:ea:Badge>');

        self::assertStringContainsString('class="icon"', $html);
        self::assertLessThan(strpos($html, 'Label'), strpos($html, 'class="icon"'));
    }

    public function testTrailingIconIsRenderedAfterLabel(): void
    {
        $html = $this->renderBadge('<twig:ea:Badge variant="info" endIcon="fa-star">Label</twig:ea:Badge>');

        self::assertStringContainsString('class="icon"', $html);
        self::assertGreaterThan(strpos($html, 'Label'), strpos($html, 'class="icon"'));
    }

    public function testLink(): void
    {
        self::assertSame(
            '<a class="badge badge-secondary" href="https://example.com">x</a>',
            $this->renderBadge('<twig:ea:Badge href="https://example.com">x</twig:ea:Badge>')
        );
    }
}
