<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ArrayConfigurator;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ArrayFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new ArrayConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = ArrayField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame(TextType::class, $fieldDto->getFormTypeOption('entry_type'));
        self::assertTrue($fieldDto->getFormTypeOption('allow_add'));
        self::assertTrue($fieldDto->getFormTypeOption('allow_delete'));
        self::assertTrue($fieldDto->getFormTypeOption('delete_empty'));
        self::assertFalse($fieldDto->getFormTypeOption('entry_options.label'));
        self::assertSame(CollectionType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-array', $fieldDto->getCssClass());
    }

    public function testFieldWithEmptyArray(): void
    {
        $field = ArrayField::new('foo');
        $field->setValue([]);
        $fieldDto = $this->configure($field);

        self::assertSame('label/empty', $fieldDto->getTemplateName());
    }

    public function testFieldWithNullValue(): void
    {
        $field = ArrayField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertSame('label/empty', $fieldDto->getTemplateName());
    }

    public function testFieldWithValuesOnIndexPage(): void
    {
        $field = ArrayField::new('foo');
        $field->setValue(['item1', 'item2', 'item3']);
        $fieldDto = $this->configure($field);

        self::assertSame('item1, item2, item3', $fieldDto->getFormattedValue());
    }

    public function testFieldWithValuesOnDetailPage(): void
    {
        $field = ArrayField::new('foo');
        $field->setValue(['item1', 'item2', 'item3']);
        $fieldDto = $this->configure($field, Crud::PAGE_DETAIL, actionName: Action::DETAIL);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertSame(['item1', 'item2', 'item3'], $fieldDto->getValue());
        self::assertNull($fieldDto->getFormattedValue());
        self::assertStringContainsString('<ul>', $html);
        self::assertStringContainsString('<li>item1</li>', $html);
        self::assertStringContainsString('<li>item2</li>', $html);
        self::assertStringContainsString('<li>item3</li>', $html);
    }

    public function testFieldWithSingleValueOnIndexPage(): void
    {
        $field = ArrayField::new('foo');
        $field->setValue(['single_item']);
        $fieldDto = $this->configure($field);

        self::assertSame('single_item', $fieldDto->getFormattedValue());
    }

    public function testFormTypeOptionsAreNotOverriddenIfAlreadySet(): void
    {
        $field = ArrayField::new('foo');
        $field->setFormTypeOption('allow_add', false);
        $field->setFormTypeOption('allow_delete', false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getFormTypeOption('allow_add'));
        self::assertFalse($fieldDto->getFormTypeOption('allow_delete'));
    }
}
