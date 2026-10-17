<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\ClickTrigger;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;

class CrudTest extends TestCase
{
    public function testAskConfirmationOnBatchActionsDefaultValue(): void
    {
        $crudConfig = Crud::new();

        $this->assertTrue($crudConfig->getAsDto()->askConfirmationOnBatchActions());
    }

    public function testAskConfirmationOnBatchActionsDisabled(): void
    {
        $crudConfig = Crud::new();
        $crudConfig->askConfirmationOnBatchActions(false);

        $this->assertFalse($crudConfig->getAsDto()->askConfirmationOnBatchActions());
    }

    public function testAskConfirmationOnBatchActionsEnabled(): void
    {
        $crudConfig = Crud::new();
        $crudConfig->askConfirmationOnBatchActions(false);
        $crudConfig->askConfirmationOnBatchActions(true);

        $this->assertTrue($crudConfig->getAsDto()->askConfirmationOnBatchActions());
    }

    public function testAskConfirmationOnBatchActionsWithCustomMessage(): void
    {
        $crudConfig = Crud::new();
        $customMessage = 'Custom confirmation for %action_name% on %num_items% items';
        $crudConfig->askConfirmationOnBatchActions($customMessage);

        $this->assertSame($customMessage, $crudConfig->getAsDto()->askConfirmationOnBatchActions());
    }

    public function testAskConfirmationOnBatchActionsWithTranslatableMessage(): void
    {
        $crudConfig = Crud::new();
        $translatableMessage = new TranslatableMessage('batch.confirm');
        $crudConfig->askConfirmationOnBatchActions($translatableMessage);

        $this->assertSame($translatableMessage, $crudConfig->getAsDto()->askConfirmationOnBatchActions());
    }

    public function testAddFormTheme(): void
    {
        $crudConfig = Crud::new();
        $crudConfig->addFormTheme('admin/form/my_theme.html.twig');

        $this->assertSame(['@EasyAdmin/crud/form_theme.html.twig', 'admin/form/my_theme.html.twig'], $crudConfig->getAsDto()->getFormThemes());
    }

    public function testSetFormThemes(): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setFormThemes(['common/base_form_theme.html.twig', 'admin/form/my_theme.html.twig']);

        $this->assertSame(['common/base_form_theme.html.twig', 'admin/form/my_theme.html.twig'], $crudConfig->getAsDto()->getFormThemes());
    }

    public function testDefaultThousandsSeparator(): void
    {
        $crudConfig = Crud::new();

        $this->assertNull($crudConfig->getAsDto()->getThousandsSeparator());
    }

    #[TestWith([',', '.', ' ', '-', ''])]
    public function testSetThousandsSeparator(string $separator): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setThousandsSeparator($separator);

        $this->assertSame($separator, $crudConfig->getAsDto()->getThousandsSeparator());
    }

    public function testDefaultDecimalSeparator(): void
    {
        $crudConfig = Crud::new();

        $this->assertNull($crudConfig->getAsDto()->getDecimalSeparator());
    }

    #[TestWith([',', '.', ' ', '-', ''])]
    public function testSetDecimalSeparator(string $separator): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setDecimalSeparator($separator);

        $this->assertSame($separator, $crudConfig->getAsDto()->getDecimalSeparator());
    }

    #[TestWith(['short'])]
    #[TestWith(['medium'])]
    #[TestWith(['long'])]
    #[TestWith(['full'])]
    // custom time pattern
    #[TestWith(['EEEE'])]
    // custom time pattern
    #[TestWith(['MMMM/d, EEEE'])]
    // invalid value used on purpose to test that the method accepts any custom pattern/format
    #[TestWith(['this is wrong'])]
    public function testSetDateFormat(string $dateFormat): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setDateFormat($dateFormat);

        $this->assertSame($dateFormat, $crudConfig->getAsDto()->getDatePattern());
    }

    #[TestWith(['none', ''])]
    public function testSetDateFormatWithInvalidFormat(string $dateFormat): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument of the "EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateFormat()" method cannot be "none" or an empty string. Use either the special date formats (short, medium, long, full) or a datetime Intl pattern.');

        $crudConfig = Crud::new();
        $crudConfig->setDateFormat($dateFormat);
    }

    #[TestWith(['short'])]
    #[TestWith(['medium'])]
    #[TestWith(['long'])]
    #[TestWith(['full'])]
    // custom time pattern
    #[TestWith(['zzzz'])]
    // custom time pattern
    #[TestWith(['ss, a, mm'])]
    // invalid value used on purpose to test that the method accepts any custom pattern/format
    #[TestWith(['this is wrong'])]
    public function testSetTimeFormat(string $timeFormat): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setTimeFormat($timeFormat);

        $this->assertSame($timeFormat, $crudConfig->getAsDto()->getTimePattern());
    }

    #[TestWith(['none', ''])]
    public function testSetTimeFormatWithInvalidFormat(string $timeFormat): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument of the "EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setTimeFormat()" method cannot be "none" or an empty string. Use either the special time formats (short, medium, long, full) or a datetime Intl pattern.');

        $crudConfig = Crud::new();
        $crudConfig->setTimeFormat($timeFormat);
    }

    #[TestWith(['none', 'short'])]
    #[TestWith(['none', 'medium'])]
    #[TestWith(['none', 'long'])]
    #[TestWith(['none', 'full'])]
    #[TestWith(['short', 'none'])]
    #[TestWith(['short', 'short'])]
    #[TestWith(['short', 'medium'])]
    #[TestWith(['short', 'long'])]
    #[TestWith(['short', 'full'])]
    #[TestWith(['medium', 'none'])]
    #[TestWith(['medium', 'short'])]
    #[TestWith(['medium', 'medium'])]
    #[TestWith(['medium', 'long'])]
    #[TestWith(['medium', 'full'])]
    #[TestWith(['long', 'none'])]
    #[TestWith(['long', 'short'])]
    #[TestWith(['long', 'medium'])]
    #[TestWith(['long', 'long'])]
    #[TestWith(['long', 'full'])]
    #[TestWith(['full', 'none'])]
    #[TestWith(['full', 'short'])]
    #[TestWith(['full', 'medium'])]
    #[TestWith(['full', 'long'])]
    #[TestWith(['full', 'full'])]
    #[TestWith(['MMMM/d, EEEE ss, a, mm', 'none'])]
    // invalid value used on purpose to test that the method accepts any custom datetime pattern
    #[TestWith(['this is wrong', 'none'])]
    public function testSetDateTimeFormat(string $dateFormat, string $timeFormat): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setDateTimeFormat($dateFormat, $timeFormat);

        $this->assertSame([$dateFormat, $timeFormat], $crudConfig->getAsDto()->getDateTimePattern());
    }

    #[TestWith(['', 'short', 'The first argument of the "EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateTimeFormat()" method cannot be an empty string. Use either a date format (none, short, medium, long, full) or a datetime Intl pattern.'])]
    #[TestWith(['none', '', 'The values of the arguments of "EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateTimeFormat()" cannot be "none" or an empty string at the same time. Change any of them (or both).'])]
    #[TestWith(['none', 'none', 'The values of the arguments of "EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateTimeFormat()" cannot be "none" or an empty string at the same time. Change any of them (or both).'])]
    #[TestWith(['EEEE', 'zzzz', 'When the first argument of "EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateTimeFormat()" is a datetime pattern, you cannot set the time format in the second argument (define the time format inside the datetime pattern).'])]
    #[TestWith(['long', 'zzz', 'When using a predefined format for the date, the time format must also be a predefined format (one of the following: none, short, medium, long, full) but "zzz" was given.'])]
    public function testSetDateTimeExceptions(string $dateFormat, string $timeFormat, string $exceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $crudConfig = Crud::new();
        $crudConfig->setDateTimeFormat($dateFormat, $timeFormat);
    }

    public function testDefaultRowActionDefaultValue(): void
    {
        $crudConfig = Crud::new();

        $this->assertSame([Action::EDIT, Action::DETAIL], $crudConfig->getAsDto()->getDefaultRowAction());
    }

    #[TestWith(['detail'])]
    #[TestWith(['edit'])]
    #[TestWith(['myCustomAction'])]
    #[TestWith([['detail', 'edit', 'customAction']])]
    public function testSetDefaultRowAction(string|array $actionNames): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setDefaultRowAction($actionNames);

        $this->assertSame($actionNames, $crudConfig->getAsDto()->getDefaultRowAction());
    }

    public function testSetDefaultRowActionWithNull(): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setDefaultRowAction(null);

        $this->assertNull($crudConfig->getAsDto()->getDefaultRowAction());
    }

    #[TestWith([''])]
    #[TestWith(['   '])]
    public function testSetDefaultRowActionWithInvalidString(string $actionName): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The default row action cannot be an empty string. Use null to disable it.');

        $crudConfig = Crud::new();
        $crudConfig->setDefaultRowAction($actionName);
    }

    #[TestWith([['edit', '']])]
    #[TestWith([['edit', '   ']])]
    public function testSetDefaultRowActionWithInvalidArray(array $actionNames): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All actions in the fallback chain must be non-empty strings.');

        $crudConfig = Crud::new();
        $crudConfig->setDefaultRowAction($actionNames);
    }

    public function testDefaultRowActionDefaultTrigger(): void
    {
        $crudConfig = Crud::new();

        $this->assertSame(ClickTrigger::SINGLE, $crudConfig->getAsDto()->getDefaultRowActionTrigger());
    }

    #[TestWith(['single'])]
    #[TestWith(['double'])]
    public function testSetDefaultRowActionDefaultTrigger(string $clickTrigger): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setDefaultRowActionTrigger($clickTrigger);

        $this->assertSame($clickTrigger, $crudConfig->getAsDto()->getDefaultRowActionTrigger());
    }

    #[TestWith([''])]
    #[TestWith(['   '])]
    #[TestWith(['invalid'])]
    public function testSetDefaultRowActionDefaultTriggerWithInvalidString(string $clickTrigger): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The default row action trigger can be only "single" or "double", "%s" given.', $clickTrigger));

        $crudConfig = Crud::new();
        $crudConfig->setDefaultRowActionTrigger($clickTrigger);
    }

    public function testSetFiltersAcceptsNull(): void
    {
        $crudConfig = Crud::new();
        $crudConfig->setFilters(null);

        $this->assertInstanceOf(FilterConfigDto::class, $crudConfig->getAsDto()->getFiltersConfig());
    }
}
