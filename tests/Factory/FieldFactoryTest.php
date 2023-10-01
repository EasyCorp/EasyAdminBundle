<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnClose;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnGroupClose;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnGroupOpen;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\Internal\EaFormColumnOpen;
use PHPUnit\Framework\TestCase;

class FieldFactoryTest extends TestCase
{
    /**
     * @dataProvider provideFormLayouts
     */
    public function testFixFormColumns(array $originalFields, array $expectedFields)
    {
        $originalFields = $this->createFormFields($originalFields);
        $expectedFields = $this->createFormFields($expectedFields);

        $fieldFactory = $this->getMockBuilder(FieldFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        // make the fixFormColumns() method public
        $method = new \ReflectionMethod(FieldFactory::class, 'fixFormColumns');
        $method->setAccessible(true);
        $method->invoke($fieldFactory, $originalFields);

        if (false=== $this->isFormLayoutTheSame($expectedFields, $originalFields)) {
            dump("EXPECTED");
            foreach ($expectedFields as $field) {
                dump($field->getFormType());
            }

            dump("ORIGINAL");
            foreach ($originalFields as $field) {
                dump($field->getFormType());
            }
        }
        $this->assertTrue($this->isFormLayoutTheSame($expectedFields, $originalFields));
    }

    /**
     * @dataProvider provideFormLayoutErrors
     */
    public function testFixFormColumnsErrors(array $originalFields, string $expectedExceptionFqcn, string $expectedExceptionMessage)
    {
        $originalFields = $this->createFormFields($originalFields);

        $fieldFactory = $this->getMockBuilder(FieldFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        // make the fixFormColumns() method public
        $method = new \ReflectionMethod(FieldFactory::class, 'fixFormColumns');
        $method->setAccessible(true);

        $this->expectException($expectedExceptionFqcn);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $method->invoke($fieldFactory, $originalFields);
    }

    public function provideFormLayouts()
    {
        yield 'No columns' => [
            ['field', 'field', 'field'],
            ['field', 'field', 'field'],
        ];

        yield 'One column for all fields' => [
            ['column', 'field', 'field', 'field'],
            ['column_group_open', 'column_open', 'field', 'field', 'field', 'column_close', 'column_group_close'],
        ];

        yield 'Two columns for all fields' => [
            ['column', 'field', 'column', 'field', 'field'],
            ['column_group_open', 'column_open', 'field', 'column_close', 'column_open', 'field', 'field', 'column_close', 'column_group_close'],
        ];

        yield 'Tabs and columns' => [
            ['tab', 'column', 'field', 'column', 'field', 'tab', 'field'],
            ['tab', 'column_group_open', 'column_open', 'field', 'column_close', 'column_open', 'field', 'column_close', 'column_group_close', 'tab', 'field'],
        ];
    }

    public function provideFormLayoutErrors()
    {
        yield 'One or more fields outside of all columns' => [
            ['field', 'column', 'field', 'field'],
            \InvalidArgumentException::class,
            'When using form columns, all fields must be rendered inside a column. However, your field "field_1" does not belong to any column. Move it under a form column or create a new form column before it.'
        ];

        yield 'Column defined at the bottom of fields' => [
            ['field', 'field', 'field', 'column'],
            \InvalidArgumentException::class,
            'When using form columns, all fields must be rendered inside a column. However, your field "field_1" does not belong to any column. Move it under a form column or create a new form column before it.'
        ];

        yield 'Tabs inside columns' => [
            ['column', 'tab', 'field', 'tab', 'field', 'field'],
            \InvalidArgumentException::class,
            'When using form columns, you can\'t define tabs inside columns (but you can define columns inside tabs). Move the tab "tab_2" outside any column.'
        ];
    }

    private function createFormFields(array $fieldDefinition): FieldCollection
    {
        return FieldCollection::new($this->doCreateFormFields($fieldDefinition));
    }

    private function doCreateFormFields(array $fields): iterable
    {
        $fieldNumber = 0;
        foreach ($fields as $fieldType) {
            ++$fieldNumber;

            yield match ($fieldType) {
                'field' => TextField::new('field_'.$fieldNumber),
                'tab' => FormField::addTab('tab_'.$fieldNumber),
                'column', 'column_open' => FormField::addColumn(8),
                'column_group_open' => Field::new('column_group_open_'.$fieldNumber)->setFormType(EaFormColumnGroupOpen::class),
                'column_group_close' => Field::new('column_group_close_'.$fieldNumber)->setFormType(EaFormColumnGroupClose::class),
                'column_close' => Field::new('column_close_'.$fieldNumber)->setFormType(EaFormColumnClose::class),
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
