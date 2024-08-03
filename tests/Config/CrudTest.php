<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use PHPUnit\Framework\TestCase;

class CrudTest extends TestCase
{
    public function testAddFormTheme()
    {
        $crudConfig = Crud::new();
        $crudConfig->addFormTheme('admin/form/my_theme.html.twig');

        $this->assertSame(['@EasyAdmin/crud/form_theme.html.twig', 'admin/form/my_theme.html.twig'], $crudConfig->getAsDto()->getFormThemes());
    }

    public function testSetFormThemes()
    {
        $crudConfig = Crud::new();
        $crudConfig->setFormThemes(['common/base_form_theme.html.twig', 'admin/form/my_theme.html.twig']);

        $this->assertSame(['common/base_form_theme.html.twig', 'admin/form/my_theme.html.twig'], $crudConfig->getAsDto()->getFormThemes());
    }

    public function testDefaultThousandsSeparator()
    {
        $crudConfig = Crud::new();

        $this->assertNull($crudConfig->getAsDto()->getThousandsSeparator());
    }

    /**
     * @testWith [",", ".", " ", "-", ""]
     */
    public function testSetThousandsSeparator(string $separator)
    {
        $crudConfig = Crud::new();
        $crudConfig->setThousandsSeparator($separator);

        $this->assertSame($separator, $crudConfig->getAsDto()->getThousandsSeparator());
    }

    public function testDefaultDecimalSeparator()
    {
        $crudConfig = Crud::new();

        $this->assertNull($crudConfig->getAsDto()->getDecimalSeparator());
    }

    /**
     * @testWith [",", ".", " ", "-", ""]
     */
    public function testSetDecimalSeparator(string $separator)
    {
        $crudConfig = Crud::new();
        $crudConfig->setDecimalSeparator($separator);

        $this->assertSame($separator, $crudConfig->getAsDto()->getDecimalSeparator());
    }

    /**
     * @testWith ["short"]
     *           ["medium"]
     *           ["long"]
     *           ["full"]
     *           // custom time pattern
     *           ["EEEE"]
     *           // custom time pattern
     *           ["MMMM/d, EEEE"]
     *           // invalid value used on purpose to test that the method accepts any custom pattern/format
     *           ["this is wrong"]
     */
    public function testSetDateFormat(string $dateFormat)
    {
        $crudConfig = Crud::new();
        $crudConfig->setDateFormat($dateFormat);

        $this->assertSame($dateFormat, $crudConfig->getAsDto()->getDatePattern());
    }

    /**
     * @testWith ["none", ""]
     */
    public function testSetDateFormatWithInvalidFormat(string $dateFormat)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument of the "EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateFormat()" method cannot be "none" or an empty string. Use either the special date formats (short, medium, long, full) or a datetime Intl pattern.');

        $crudConfig = Crud::new();
        $crudConfig->setDateFormat($dateFormat);
    }

    /**
     * @testWith ["short"]
     *           ["medium"]
     *           ["long"]
     *           ["full"]
     *           // custom time pattern
     *           ["zzzz"]
     *           // custom time pattern
     *           ["ss, a, mm"]
     *           // invalid value used on purpose to test that the method accepts any custom pattern/format
     *           ["this is wrong"]
     */
    public function testSetTimeFormat(string $timeFormat)
    {
        $crudConfig = Crud::new();
        $crudConfig->setTimeFormat($timeFormat);

        $this->assertSame($timeFormat, $crudConfig->getAsDto()->getTimePattern());
    }

    /**
     * @testWith ["none", ""]
     */
    public function testSetTimeFormatWithInvalidFormat(string $timeFormat)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The first argument of the "EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setTimeFormat()" method cannot be "none" or an empty string. Use either the special time formats (short, medium, long, full) or a datetime Intl pattern.');

        $crudConfig = Crud::new();
        $crudConfig->setTimeFormat($timeFormat);
    }

    /**
     * @testWith ["none", "short"]
     *           ["none", "medium"]
     *           ["none", "long"]
     *           ["none", "full"]
     *           ["short", "none"]
     *           ["short", "short"]
     *           ["short", "medium"]
     *           ["short", "long"]
     *           ["short", "full"]
     *           ["medium", "none"]
     *           ["medium", "short"]
     *           ["medium", "medium"]
     *           ["medium", "long"]
     *           ["medium", "full"]
     *           ["long", "none"]
     *           ["long", "short"]
     *           ["long", "medium"]
     *           ["long", "long"]
     *           ["long", "full"]
     *           ["full", "none"]
     *           ["full", "short"]
     *           ["full", "medium"]
     *           ["full", "long"]
     *           ["full", "full"]
     *           ["MMMM/d, EEEE ss, a, mm", "none"]
     *           // the following invalid value are used on purpose to test that the method accepts any custom formats/patterns
     *           ["this is wrong", "this is wrong"]
     */
    public function testSetDateTimeFormat(string $dateFormat, string $timeFormat)
    {
        $crudConfig = Crud::new();
        $crudConfig->setDateTimeFormat($dateFormat, $timeFormat);

        $this->assertSame([$dateFormat, $timeFormat], $crudConfig->getAsDto()->getDateTimePattern());
    }

    /**
     * @testWith ["", "short", "The first argument of the \"EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateTimeFormat()\" method cannot be an empty string. Use either a date format (none, short, medium, long, full) or a datetime Intl pattern."]
     *           ["none", "", "The values of the arguments of \"EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateTimeFormat()\" cannot be \"none\" or an empty string at the same time. Change any of them (or both)."]
     *           ["none", "none", "The values of the arguments of \"EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateTimeFormat()\" cannot be \"none\" or an empty string at the same time. Change any of them (or both)."]
     *           ["EEEE", "zzzz", "When the first argument of \"EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud::setDateTimeFormat()\" is a datetime pattern, you cannot set the time format in the second argument (define the time format inside the datetime pattern)."]
     *           ["long", "zzz", "When using a predefined format for the date, the time format must also be a predefined format (one of the following: none, short, medium, long, full) but \"zzz\" was given."]
     */
    public function testSetDateTimeExceptions(string $dateFormat, string $timeFormat, string $exceptionMessage)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $crudConfig = Crud::new();
        $crudConfig->setDateTimeFormat($dateFormat, $timeFormat);
    }
}
