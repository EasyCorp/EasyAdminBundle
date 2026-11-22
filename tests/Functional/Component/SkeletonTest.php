<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component\Fixtures\StringOption;

/**
 * Rendering tests for the <twig:ea:Skeleton> component.
 */
class SkeletonTest extends AbstractFieldFunctionalTestCase
{
    public function testVariantAsString(): void
    {
        self::assertStringContainsString('class="ea-skeleton ea-skeleton-block"', $this->renderSkeleton('<twig:ea:Skeleton variant="block" />'));
    }

    public function testVariantAsEnumCase(): void
    {
        self::assertStringContainsString('class="ea-skeleton ea-skeleton-block"', $this->renderSkeleton('<twig:ea:Skeleton :variant="variant" />', ['variant' => StringOption::Block]));
    }

    public function testUnknownVariantFallsBackToText(): void
    {
        self::assertStringContainsString('class="ea-skeleton ea-skeleton-text"', $this->renderSkeleton('<twig:ea:Skeleton variant="circle" />'));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderSkeleton(string $template, array $context = []): string
    {
        return static::getContainer()->get('twig')->createTemplate($template)->render($context);
    }
}
