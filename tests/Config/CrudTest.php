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
}
