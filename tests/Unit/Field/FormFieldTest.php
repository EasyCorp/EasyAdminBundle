<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Dto\FormTabBadgeDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class FormFieldTest extends TestCase
{
    #[DataProvider('defaultPropertySuffixProvider')]
    public function testDefaultSetPropertySuffix(FormField $formField): void
    {
        $this->assertTrue(Ulid::isValid($formField->getAsDto()->getPropertyNameSuffix()));
    }

    public static function defaultPropertySuffixProvider(): \Generator
    {
        yield [FormField::addFieldset()];
        yield [FormField::addColumn()];
        yield [FormField::addRow()];
        yield [FormField::addTab()];
    }

    #[DataProvider('propertySuffixProvider')]
    public function testSetPropertySuffix(FormField $formField, string $expectedPropertyName, string $expectedPropertyNameSuffix): void
    {
        $dto = $formField->getAsDto();
        $this->assertSame($expectedPropertyName, $dto->getPropertyNameWithSuffix());
        $this->assertSame($expectedPropertyNameSuffix, $dto->getPropertyNameSuffix());
    }

    public static function propertySuffixProvider(): \Generator
    {
        yield [FormField::addFieldset()->setPropertySuffix('foo'), 'ea_form_fieldset_foo', 'foo'];
        yield [FormField::addColumn()->setPropertySuffix('foo'), 'ea_form_column_foo', 'foo'];
        yield [FormField::addRow()->setPropertySuffix('foo'), 'ea_form_row_foo', 'foo'];
        yield [FormField::addTab()->setPropertySuffix('foo'), 'ea_form_tab_foo', 'foo'];
    }

    public function testSetBadgeStoresBadgeDto(): void
    {
        $dto = FormField::addTab('Bars')->setBadge(7, 'info', ['data-foo' => 'bar'])->getAsDto();

        $badge = $dto->getCustomOption(FormField::OPTION_TAB_BADGE);
        $this->assertInstanceOf(FormTabBadgeDto::class, $badge);
        $this->assertSame(7, $badge->getContent());
        $this->assertSame('badge-info', $badge->getCssClass());
        $this->assertSame('', $badge->getHtmlStyle());
        $this->assertSame(['data-foo' => 'bar'], $badge->getHtmlAttributes());
    }

    public function testSetBadgeWithDefaultStyle(): void
    {
        $badge = FormField::addTab('Bars')->setBadge('New')->getAsDto()->getCustomOption(FormField::OPTION_TAB_BADGE);

        $this->assertSame('badge-secondary', $badge->getCssClass());
    }

    public function testSetBadgeWithCustomStyle(): void
    {
        $badge = FormField::addTab('Bars')->setBadge(3, 'color: red;')->getAsDto()->getCustomOption(FormField::OPTION_TAB_BADGE);

        $this->assertSame('', $badge->getCssClass());
        $this->assertSame('color: red;', $badge->getHtmlStyle());
    }

    public function testSetBadgeKeepsCallableUnresolved(): void
    {
        $callable = static fn ($entity) => 42;
        $badge = FormField::addTab('Bars')->setBadge($callable)->getAsDto()->getCustomOption(FormField::OPTION_TAB_BADGE);

        $this->assertSame($callable, $badge->getContent());
    }

    public function testSetBadgeThrowsOnNonTabFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FormField::addFieldset('Foo')->setBadge(7);
    }

    public function testSetTabId(): void
    {
        $dto = FormField::addTab('Bars')->setTabId('bars')->getAsDto();

        $this->assertSame('bars', $dto->getCustomOption(FormField::OPTION_TAB_ID));
    }

    public function testSetTabIdThrowsOnNonTabFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FormField::addFieldset('Foo')->setTabId('foo');
    }

    #[DataProvider('provideInvalidTabIds')]
    public function testSetTabIdThrowsOnIdsThatAreNotUrlSafe(string $id): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The tab ID "%s"', $id));

        FormField::addTab('Bars')->setTabId($id);
    }

    public static function provideInvalidTabIds(): \Generator
    {
        yield 'empty' => [''];
        yield 'non-ASCII letters' => ['información'];
        yield 'whitespace' => ['contact info'];
        yield 'hash' => ['contact#info'];
        yield 'percent' => ['contact%20info'];
    }
}
