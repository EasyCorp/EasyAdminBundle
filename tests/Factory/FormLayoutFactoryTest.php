<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormLayoutFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnGroupCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormColumnGroupOpenType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormFieldsetCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabListType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneGroupCloseType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Layout\EaFormTabPaneGroupOpenType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\IdentityTranslator;

class FormLayoutFactoryTest extends TestCase
{
    /**
     * @dataProvider provideFormLayouts
     */
    public function testFixFormColumns(array $fieldConfig, string $expectedLayout)
    {
        $originalFields = $this->createFormFieldsFromConfig($fieldConfig);
        $expectedFields = $this->createFormFieldsFromLayout($expectedLayout);

        $formLayoutFactory = new FormLayoutFactory(new IdentityTranslator());
        $formLayoutFactory->createLayout($originalFields, Crud::PAGE_EDIT);

        $this->assertTrue($this->isFormLayoutTheSame($expectedFields, $originalFields));
    }

    /**
     * @dataProvider provideFormLayoutErrors
     */
    public function testFixFormColumnsErrors(array $originalFields, string $expectedExceptionFqcn, string $expectedExceptionMessage)
    {
        $this->expectException($expectedExceptionFqcn);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $originalFields = $this->createFormFieldsFromConfig($originalFields);

        $fieldFactory = new FormLayoutFactory(new IdentityTranslator());
        $fieldFactory->createLayout($originalFields, Crud::PAGE_EDIT);
    }

    public function provideFormLayouts()
    {
        yield 'Only fields (a fieldset is added automatically to wrap all fields)' => [
            ['field', 'field', 'field'],
            <<<LAYOUT
                fieldset_open
                    field
                    field
                    field
                fieldset_close
            LAYOUT,
        ];

        yield 'One fieldset for all fields' => [
            ['fieldset', 'field', 'field', 'field'],
            <<<LAYOUT
                fieldset_open
                    field
                    field
                    field
                fieldset_close
            LAYOUT,
        ];

        yield 'Two fieldsets for all fields' => [
            ['fieldset', 'field', 'fieldset', 'field', 'field'],
            <<<LAYOUT
                fieldset_open
                    field
                fieldset_close
                fieldset_open
                    field
                    field
                fieldset_close
            LAYOUT,
        ];

        yield 'A field outside of all fieldsets is included in an automatic fieldset' => [
            ['field', 'fieldset', 'field', 'field'],
            <<<LAYOUT
                fieldset_open
                    field
                fieldset_close
                fieldset_open
                    field
                    field
                fieldset_close
            LAYOUT,
        ];

        yield 'A field outside of all fieldsets but inside a column is included in an automatic fieldset' => [
            ['column', 'field', 'fieldset', 'field', 'field'],
            <<<LAYOUT
                column_group_open
                    column_open
                        fieldset_open
                            field
                        fieldset_close
                        fieldset_open
                            field
                            field
                        fieldset_close
                    column_close
                column_group_close
            LAYOUT,
        ];

        yield 'Multiple fields outside of all fieldsets but inside a column are included in an automatic fieldset' => [
            ['column', 'field', 'fieldset', 'field', 'field', 'column', 'field', 'fieldset', 'field'],
            <<<LAYOUT
                column_group_open
                    column_open
                        fieldset_open
                            field
                        fieldset_close
                        fieldset_open
                            field
                            field
                        fieldset_close
                    column_close
                    column_open
                        fieldset_open
                            field
                        fieldset_close
                        fieldset_open
                            field
                        fieldset_close
                    column_close
                column_group_close
            LAYOUT,
        ];

        yield 'A field outside of all fieldsets but inside a tab is included in an automatic fieldset' => [
            ['tab', 'field', 'fieldset', 'field', 'field'],
            <<<LAYOUT
                tab_list
                tab_pane_group_open
                    tab_pane_open
                        fieldset_open
                            field
                        fieldset_close
                        fieldset_open
                            field
                            field
                        fieldset_close
                    tab_pane_close
                tab_pane_group_close
            LAYOUT,
        ];

        yield 'Multiple fields outside of all fieldsets but inside a column or tab are included in an automatic fieldset' => [
            ['tab', 'column', 'field', 'fieldset', 'field', 'column', 'field', 'fieldset', 'field', 'tab', 'field', 'fieldset', 'field'],
            <<<LAYOUT
                tab_list
                tab_pane_group_open
                    tab_pane_open
                        column_group_open
                            column_open
                                fieldset_open
                                    field
                                fieldset_close
                                fieldset_open
                                    field
                                fieldset_close
                            column_close
                            column_open
                                fieldset_open
                                    field
                                fieldset_close
                                fieldset_open
                                    field
                                fieldset_close
                            column_close
                        column_group_close
                    tab_pane_close
                    tab_pane_open
                        fieldset_open
                            field
                        fieldset_close
                        fieldset_open
                            field
                        fieldset_close
                   tab_pane_close
               tab_pane_group_close
            LAYOUT,
        ];

        yield 'One column for all fields' => [
            ['column', 'field', 'field', 'field'],
            <<<LAYOUT
                column_group_open
                    column_open
                        field
                        field
                        field
                    column_close
                column_group_close
            LAYOUT,
        ];

        yield 'Two columns for all fields' => [
            ['column', 'field', 'column', 'field', 'field'],
            <<<LAYOUT
                column_group_open
                    column_open
                        field
                    column_close
                    column_open
                        field
                        field
                    column_close
                column_group_close
            LAYOUT,
        ];

        yield 'Fieldsets and columns' => [
            ['column', 'fieldset', 'field', 'column', 'fieldset', 'field', 'field'],
            <<<LAYOUT
                column_group_open
                    column_open
                        fieldset_open
                            field
                        fieldset_close
                    column_close
                    column_open
                        fieldset_open
                            field
                            field
                        fieldset_close
                    column_close
                column_group_close
            LAYOUT,
        ];

        yield 'Tabs and columns' => [
            ['tab', 'column', 'field', 'column', 'field', 'tab', 'field'],
            <<<LAYOUT
                tab_list
                tab_pane_group_open
                    tab_pane_open
                        column_group_open
                            column_open
                                field
                            column_close
                            column_open
                                field
                            column_close
                        column_group_close
                    tab_pane_close
                    tab_pane_open
                        field
                    tab_pane_close
                tab_pane_group_close
            LAYOUT,
        ];

        yield 'Tabs, fieldsets and columns' => [
            ['tab', 'column', 'fieldset', 'field', 'column', 'field', 'tab', 'fieldset', 'field'],
            <<<LAYOUT
                tab_list
                tab_pane_group_open
                    tab_pane_open
                        column_group_open
                            column_open
                                fieldset_open
                                    field
                                fieldset_close
                            column_close
                            column_open
                                field
                            column_close
                        column_group_close
                    tab_pane_close
                    tab_pane_open
                        fieldset_open
                            field
                        fieldset_close
                    tab_pane_close
                tab_pane_group_close
            LAYOUT,
        ];
    }

    public function provideFormLayoutErrors()
    {
        yield 'One or more fields outside of all columns' => [
            ['field', 'column', 'field', 'field'],
            \InvalidArgumentException::class,
            'When using form columns, all fields must be rendered inside a column. However, your field "field_1" does not belong to any column. Move it under a form column or create a new form column before it.',
        ];

        yield 'Column defined at the bottom of fields' => [
            ['field', 'field', 'field', 'column'],
            \InvalidArgumentException::class,
            'When using form columns, all fields must be rendered inside a column. However, your field "field_1" does not belong to any column. Move it under a form column or create a new form column before it.',
        ];

        yield 'Tabs inside columns' => [
            ['column', 'tab', 'field', 'tab', 'field', 'field'],
            \InvalidArgumentException::class,
            'When using form columns, you can\'t define tabs inside columns (but you can define columns inside tabs). Move the tab "tab_pane_open_2" outside any column.',
        ];

        yield 'Fields outside tabs' => [
            ['field', 'tab', 'field', 'field', 'tab', 'field'],
            \InvalidArgumentException::class,
            'When using form tabs, all fields must be rendered inside a tab. However, your field "field_1" does not belong to any tab. Move it under a form tab or create a new form tab before it',
        ];
    }

    private function createFormFieldsFromConfig(array $fieldDefinition): FieldCollection
    {
        return FieldCollection::new($this->doCreateFormFields($fieldDefinition));
    }

    private function createFormFieldsFromLayout(string $layoutDescription): FieldCollection
    {
        $fieldNames = [];

        foreach (preg_split("/[\s]+/", $layoutDescription) as $fieldDefinition) {
            if ('' === $trimmedFieldName = trim($fieldDefinition)) {
                continue;
            }

            $fieldNames[] = $trimmedFieldName;
        }

        return $this->createFormFieldsFromConfig($fieldNames);
    }

    private function doCreateFormFields(array $fields): iterable
    {
        $fieldNumber = 0;
        foreach ($fields as $fieldType) {
            ++$fieldNumber;

            yield match ($fieldType) {
                'field' => TextField::new('field_'.$fieldNumber),
                'fieldset', 'fieldset_open' => FormField::addFieldset(),
                'fieldset_close' => Field::new('fieldset_open_'.$fieldNumber)->setFormType(EaFormFieldsetCloseType::class),
                'tab_list' => Field::new('tab_list_'.$fieldNumber)->setFormType(EaFormTabListType::class),
                'tab', 'tab_pane_open' => FormField::addTab('tab_pane_open_'.$fieldNumber),
                'tab_pane_close' => Field::new('tab_pane_close_'.$fieldNumber)->setFormType(EaFormTabPaneCloseType::class),
                'tab_pane_group_open' => Field::new('tab_pane_group_open_'.$fieldNumber)->setFormType(EaFormTabPaneGroupOpenType::class),
                'tab_pane_group_close' => Field::new('tab_pane_group_close_'.$fieldNumber)->setFormType(EaFormTabPaneGroupCloseType::class),
                'column', 'column_open' => FormField::addColumn(8),
                'column_group_open' => Field::new('column_group_open_'.$fieldNumber)->setFormType(EaFormColumnGroupOpenType::class),
                'column_group_close' => Field::new('column_group_close_'.$fieldNumber)->setFormType(EaFormColumnGroupCloseType::class),
                'column_close' => Field::new('column_close_'.$fieldNumber)->setFormType(EaFormColumnCloseType::class),
                default => Field::new('field'.$fieldNumber),
            };
        }
    }

    private function isFormLayoutTheSame(FieldCollection $expectedFields, FieldCollection $originalFields): bool
    {
        /** @var FieldDto[] $originalFieldsAsArray */
        $originalFieldsAsArray = array_values(iterator_to_array($originalFields));
        /** @var FieldDto[] $expectedFieldsAsArray */
        $expectedFieldsAsArray = array_values(iterator_to_array($expectedFields));

        foreach ($expectedFieldsAsArray as $i => $expectedField) {
            $originalField = $originalFieldsAsArray[$i];
            if ($expectedField->getFormType() !== $originalField->getFormType()) {
                return false;
            }
        }

        return true;
    }
}
