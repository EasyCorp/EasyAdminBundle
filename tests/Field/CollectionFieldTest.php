<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CollectionFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // CollectionField configurator is complex and requires services:
        // let's use a no-op configurator to test the field options
        $this->configurator = new class implements FieldConfiguratorInterface {
            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return CollectionField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                // No-op for basic option testing
            }
        };
    }

    public function testDefaultOptions(): void
    {
        $field = CollectionField::new('items');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_ALLOW_ADD));
        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_ALLOW_DELETE));
        self::assertNull($fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_IS_COMPLEX));
        self::assertNull($fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_TYPE));
        self::assertNull($fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_TO_STRING_METHOD));
        self::assertFalse($fieldDto->getCustomOption(CollectionField::OPTION_SHOW_ENTRY_LABEL));
        self::assertFalse($fieldDto->getCustomOption(CollectionField::OPTION_RENDER_EXPANDED));
        self::assertFalse($fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM));
        self::assertSame(CollectionType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-collection', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValue(): void
    {
        $field = CollectionField::new('items');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithArrayValue(): void
    {
        $field = CollectionField::new('items');
        $field->setValue(['item1', 'item2', 'item3']);
        $fieldDto = $this->configure($field);

        self::assertCount(3, $fieldDto->getValue());
    }

    public function testAllowAdd(): void
    {
        $field = CollectionField::new('items');
        $field->allowAdd();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_ALLOW_ADD));
    }

    public function testDisallowAdd(): void
    {
        $field = CollectionField::new('items');
        $field->allowAdd(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CollectionField::OPTION_ALLOW_ADD));
    }

    public function testAllowDelete(): void
    {
        $field = CollectionField::new('items');
        $field->allowDelete();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_ALLOW_DELETE));
    }

    public function testDisallowDelete(): void
    {
        $field = CollectionField::new('items');
        $field->allowDelete(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CollectionField::OPTION_ALLOW_DELETE));
    }

    public function testSetEntryIsComplex(): void
    {
        $field = CollectionField::new('items');
        $field->setEntryIsComplex();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_IS_COMPLEX));
    }

    public function testSetEntryIsNotComplex(): void
    {
        $field = CollectionField::new('items');
        $field->setEntryIsComplex(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_IS_COMPLEX));
    }

    public function testSetEntryType(): void
    {
        $field = CollectionField::new('items');
        $field->setEntryType(TextType::class);
        $fieldDto = $this->configure($field);

        self::assertSame(TextType::class, $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_TYPE));
    }

    public function testSetEntryToStringMethodWithString(): void
    {
        $field = CollectionField::new('items');
        $field->setEntryToStringMethod('getName');
        $fieldDto = $this->configure($field);

        self::assertSame('getName', $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_TO_STRING_METHOD));
    }

    public function testSetEntryToStringMethodWithCallable(): void
    {
        $callable = static fn ($entry): string => (string) $entry;
        $field = CollectionField::new('items');
        $field->setEntryToStringMethod($callable);
        $fieldDto = $this->configure($field);

        self::assertSame($callable, $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_TO_STRING_METHOD));
    }

    public function testShowEntryLabel(): void
    {
        $field = CollectionField::new('items');
        $field->showEntryLabel();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_SHOW_ENTRY_LABEL));
    }

    public function testHideEntryLabel(): void
    {
        $field = CollectionField::new('items');
        $field->showEntryLabel(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CollectionField::OPTION_SHOW_ENTRY_LABEL));
    }

    public function testRenderExpanded(): void
    {
        $field = CollectionField::new('items');
        $field->renderExpanded();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_RENDER_EXPANDED));
    }

    public function testRenderCollapsed(): void
    {
        $field = CollectionField::new('items');
        $field->renderExpanded(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(CollectionField::OPTION_RENDER_EXPANDED));
    }

    public function testUseEntryCrudForm(): void
    {
        $crudControllerFqcn = 'App\\Controller\\ItemCrudController';
        $field = CollectionField::new('items');
        $field->useEntryCrudForm($crudControllerFqcn);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM));
        self::assertSame($crudControllerFqcn, $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_CRUD_CONTROLLER_FQCN));
    }

    public function testUseEntryCrudFormWithPageNames(): void
    {
        $crudControllerFqcn = 'App\\Controller\\ItemCrudController';
        $field = CollectionField::new('items');
        $field->useEntryCrudForm($crudControllerFqcn, 'custom_new', 'custom_edit');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_USES_CRUD_FORM));
        self::assertSame('custom_new', $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_CRUD_NEW_PAGE_NAME));
        self::assertSame('custom_edit', $fieldDto->getCustomOption(CollectionField::OPTION_ENTRY_CRUD_EDIT_PAGE_NAME));
    }
}
