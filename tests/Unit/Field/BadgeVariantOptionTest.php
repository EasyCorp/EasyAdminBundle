<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the setBadgeVariant() method of all the fields that render their value as badges.
 */
class BadgeVariantOptionTest extends TestCase
{
    /**
     * @param class-string $fieldFqcn
     */
    #[DataProvider('provideFieldsRenderedAsBadges')]
    public function testDefaultBadgeVariant(string $fieldFqcn): void
    {
        $fieldDto = $fieldFqcn::new('foo')->getAsDto();

        self::assertTrue($fieldDto->getCustomOptions()->has('badgeVariant'));
        self::assertNull($fieldDto->getCustomOption('badgeVariant'));
    }

    /**
     * @param class-string $fieldFqcn
     */
    #[DataProvider('provideFieldsRenderedAsBadges')]
    public function testSetBadgeVariant(string $fieldFqcn): void
    {
        foreach (['light', 'solid', 'outline', 'dot'] as $variant) {
            $fieldDto = $fieldFqcn::new('foo')->setBadgeVariant($variant)->getAsDto();

            self::assertSame($variant, $fieldDto->getCustomOption('badgeVariant'));
        }
    }

    /**
     * @param class-string $fieldFqcn
     */
    #[DataProvider('provideFieldsRenderedAsBadges')]
    public function testSetBadgeVariantAsEnum(string $fieldFqcn): void
    {
        $fieldDto = $fieldFqcn::new('foo')->setBadgeVariant(BadgeVariant::Dot)->getAsDto();

        self::assertSame('dot', $fieldDto->getCustomOption('badgeVariant'));
    }

    /**
     * @param class-string $fieldFqcn
     */
    #[DataProvider('provideFieldsRenderedAsBadges')]
    public function testSetBadgeVariantWithInvalidValue(string $fieldFqcn): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "invalid" badge variant is not valid. Use one of these values: "light", "solid", "outline", "dot".');

        $fieldFqcn::new('foo')->setBadgeVariant('invalid');
    }

    /**
     * @param class-string $fieldFqcn
     */
    #[DataProvider('provideFieldsRenderedAsBadges')]
    public function testSetBadgeVariantRejectsDeprecatedColorCases(string $fieldFqcn): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "success" badge variant is not valid.');

        $fieldFqcn::new('foo')->setBadgeVariant(BadgeVariant::Success);
    }

    public static function provideFieldsRenderedAsBadges(): iterable
    {
        yield 'AssociationField' => [AssociationField::class];
        yield 'BooleanField' => [BooleanField::class];
        yield 'ChoiceField' => [ChoiceField::class];
        yield 'CollectionField' => [CollectionField::class];
        yield 'CurrencyField' => [CurrencyField::class];
        yield 'LanguageField' => [LanguageField::class];
        yield 'LocaleField' => [LocaleField::class];
    }
}
