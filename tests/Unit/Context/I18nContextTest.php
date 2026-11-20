<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Context;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextDirection;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use PHPUnit\Framework\TestCase;

class I18nContextTest extends TestCase
{
    public function testForTestingWithDefaults(): void
    {
        $context = I18nContext::forTesting();

        $i18n = $context->getI18n();
        self::assertSame('en', $i18n->getLocale());
        self::assertSame(TextDirection::LTR, $i18n->getTextDirection());
        self::assertSame('messages', $i18n->getTranslationDomain());
    }

    public function testForTestingWithCustomValues(): void
    {
        $context = I18nContext::forTesting(
            locale: 'es',
            textDirection: TextDirection::RTL,
            translationDomain: 'admin',
        );

        $i18n = $context->getI18n();
        self::assertSame('es', $i18n->getLocale());
        self::assertSame(TextDirection::RTL, $i18n->getTextDirection());
        self::assertSame('admin', $i18n->getTranslationDomain());
    }

    public function testOverrideTemplate(): void
    {
        $context = I18nContext::forTesting();
        self::assertSame('@EasyAdmin/layout.html.twig', $context->getTemplatePath('layout'));

        $context->overrideTemplate('layout', 'admin/layout.html.twig');

        self::assertSame('admin/layout.html.twig', $context->getTemplatePath('layout'));
    }

    public function testOverrideTemplateFailsForUndefinedTemplateNames(): void
    {
        $context = I18nContext::forTesting();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "foo" template is not defined in EasyAdmin.');

        $context->overrideTemplate('foo', 'admin/foo.html.twig');
    }
}
