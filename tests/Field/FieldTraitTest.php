<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests;

use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use PHPUnit\Framework\TestCase;

class FieldTraitTest extends TestCase
{
    public function testSetFormTypeOption()
    {
        $field = TextField::new('test');
        $field->setFormTypeOption('entry_options.attr.class', 'foo');

        $formTypeOptions = $field->getAsDto()->getFormTypeOptions();
        $this->assertSame('foo', $formTypeOptions['entry_options']['attr']['class']);
    }

    public function testSetFormTypeOptionIfNotSet()
    {
        $field = TextField::new('test');
        $field->setFormTypeOption('entry_options.attr.class', 'foo');
        $field->setFormTypeOptionIfNotSet('entry_options.attr.class', 'bar');
        $field->setFormTypeOptionIfNotSet('entry_options.attr.id', 'bar');

        $formTypeOptions = $field->getAsDto()->getFormTypeOptions();
        $this->assertSame('foo', $formTypeOptions['entry_options']['attr']['class']);
        $this->assertSame('bar', $formTypeOptions['entry_options']['attr']['id']);
    }
}
