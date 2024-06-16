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
}
