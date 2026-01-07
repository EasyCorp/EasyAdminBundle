<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class AssociationFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // AssociationField configurator requires Doctrine and other services:
        // let's use a no-op configurator to test the field options
        $this->configurator = new class implements FieldConfiguratorInterface {
            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return AssociationField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                // no-op for basic option testing
            }
        };
    }

    public function testDefaultOptions(): void
    {
        $field = AssociationField::new('category');
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE));
        self::assertNull($fieldDto->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER));
        self::assertSame(AssociationField::WIDGET_AUTOCOMPLETE, $fieldDto->getCustomOption(AssociationField::OPTION_WIDGET));
        self::assertNull($fieldDto->getCustomOption(AssociationField::OPTION_QUERY_BUILDER_CALLABLE));
        self::assertFalse($fieldDto->getCustomOption(AssociationField::OPTION_RENDER_AS_EMBEDDED_FORM));
        self::assertTrue($fieldDto->getCustomOption(AssociationField::OPTION_ESCAPE_HTML_CONTENTS));
        self::assertSame(EntityType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-association', $fieldDto->getCssClass());
    }

    public function testFieldWithNullValue(): void
    {
        $field = AssociationField::new('category');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testAutocomplete(): void
    {
        $field = AssociationField::new('category');
        $field->autocomplete();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE));
    }

    public function testRenderAsNativeWidget(): void
    {
        $field = AssociationField::new('category');
        $field->renderAsNativeWidget();
        $fieldDto = $this->configure($field);

        self::assertSame(AssociationField::WIDGET_NATIVE, $fieldDto->getCustomOption(AssociationField::OPTION_WIDGET));
    }

    public function testRenderAsAutocompleteWidget(): void
    {
        $field = AssociationField::new('category');
        $field->renderAsNativeWidget(false);
        $fieldDto = $this->configure($field);

        self::assertSame(AssociationField::WIDGET_AUTOCOMPLETE, $fieldDto->getCustomOption(AssociationField::OPTION_WIDGET));
    }

    public function testSetCrudController(): void
    {
        $crudControllerFqcn = 'App\\Controller\\CategoryCrudController';
        $field = AssociationField::new('category');
        $field->setCrudController($crudControllerFqcn);
        $fieldDto = $this->configure($field);

        self::assertSame($crudControllerFqcn, $fieldDto->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER));
    }

    public function testSetQueryBuilder(): void
    {
        $queryBuilder = static fn (QueryBuilder $qb): QueryBuilder => $qb->andWhere('entity.active = true');
        $field = AssociationField::new('category');
        $field->setQueryBuilder($queryBuilder);
        $fieldDto = $this->configure($field);

        self::assertSame($queryBuilder, $fieldDto->getCustomOption(AssociationField::OPTION_QUERY_BUILDER_CALLABLE));
    }

    public function testRenderAsEmbeddedForm(): void
    {
        $field = AssociationField::new('category');
        $field->renderAsEmbeddedForm();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(AssociationField::OPTION_RENDER_AS_EMBEDDED_FORM));
    }

    public function testRenderAsEmbeddedFormWithController(): void
    {
        $crudControllerFqcn = 'App\\Controller\\CategoryCrudController';
        $field = AssociationField::new('category');
        $field->renderAsEmbeddedForm($crudControllerFqcn);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(AssociationField::OPTION_RENDER_AS_EMBEDDED_FORM));
        self::assertSame($crudControllerFqcn, $fieldDto->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_CONTROLLER));
    }

    public function testRenderAsEmbeddedFormWithPageNames(): void
    {
        $crudControllerFqcn = 'App\\Controller\\CategoryCrudController';
        $field = AssociationField::new('category');
        $field->renderAsEmbeddedForm($crudControllerFqcn, 'custom_new', 'custom_edit');
        $fieldDto = $this->configure($field);

        self::assertSame('custom_new', $fieldDto->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_NEW_PAGE_NAME));
        self::assertSame('custom_edit', $fieldDto->getCustomOption(AssociationField::OPTION_EMBEDDED_CRUD_FORM_EDIT_PAGE_NAME));
    }

    public function testSetSortProperty(): void
    {
        $field = AssociationField::new('category');
        $field->setSortProperty('name');
        $fieldDto = $this->configure($field);

        self::assertSame('name', $fieldDto->getCustomOption(AssociationField::OPTION_SORT_PROPERTY));
    }

    public function testRenderAsHtml(): void
    {
        $field = AssociationField::new('category');
        $field->renderAsHtml();
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(AssociationField::OPTION_ESCAPE_HTML_CONTENTS));
    }

    public function testDontRenderAsHtml(): void
    {
        $field = AssociationField::new('category');
        $field->renderAsHtml(false);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(AssociationField::OPTION_ESCAPE_HTML_CONTENTS));
    }
}
