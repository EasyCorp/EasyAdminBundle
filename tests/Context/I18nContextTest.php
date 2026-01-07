<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Context;

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
}
