<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Type;

use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormLayoutFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudFormType;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Uid\Ulid;

class CrudFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                ['ea_crud' => new CrudFormType($this->createStub(DoctrineOrmTypeGuesser::class))],
                [],
            ),
        ];
    }

    /**
     * @dataProvider ulidSuffix
     */
    public function testUlidSuffix(FormField $field, array $expectedFormChildrenKeys, bool $useLayout = false): void
    {
        $this->assertTrue(Ulid::isValid($ulidSuffix = $field->getAsDto()->getPropertyNameSuffix()));

        if ($useLayout) {
            $fieldCollection = (new FormLayoutFactory(new IdentityTranslator()))->createLayout(
                FieldCollection::new([$field]),
                Crud::PAGE_NEW,
            );
        } else {
            $fieldCollection = FieldCollection::new([$field]);
        }

        $form = $this->factory->create(CrudFormType::class, null, [
            'entityDto' => $this->createEntityDto($fieldCollection),
        ]);

        $expectedFormChildrenKeys = array_map(
            static fn (string $expectedFormChildKey) => $expectedFormChildKey.$ulidSuffix,
            $expectedFormChildrenKeys,
        );

        foreach ($expectedFormChildrenKeys as $expectedFormChildKey) {
            $this->assertContains($expectedFormChildKey, array_keys($form->all()));
        }
    }

    public static function ulidSuffix(): \Generator
    {
        yield [FormField::addFieldset(), ['ea_form_fieldset_']];
        yield [FormField::addRow(), ['ea_form_row_']];
        yield [FormField::addTab(), ['ea_form_tab_']];
        yield [FormField::addFieldset(), ['ea_form_fieldset_', 'ea_form_fieldset_close_'], true];
        yield [FormField::addColumn(), ['ea_form_column_', 'ea_form_column_close_'], true];
    }

    /**
     * @dataProvider propertySuffix
     */
    public function testPropertySuffix(FormField $field, array $expectedFormChildrenKeys, bool $useLayout = false): void
    {
        if ($useLayout) {
            $fieldCollection = (new FormLayoutFactory(new IdentityTranslator()))->createLayout(
                FieldCollection::new([$field]),
                Crud::PAGE_NEW,
            );
        } else {
            $fieldCollection = FieldCollection::new([$field]);
        }

        $form = $this->factory->create(CrudFormType::class, null, [
            'entityDto' => $this->createEntityDto($fieldCollection),
        ]);

        foreach ($expectedFormChildrenKeys as $expectedFormChildKey) {
            $this->assertContains($expectedFormChildKey, array_keys($form->all()));
        }
    }

    public static function propertySuffix(): \Generator
    {
        yield [FormField::addRow(propertySuffix: 'foobar'), ['ea_form_row_foobar']];
        yield [FormField::addTab(propertySuffix: 'foobar'), ['ea_form_tab_foobar']];
        yield [FormField::addFieldset(propertySuffix: 'foobar'), ['ea_form_fieldset_foobar']];
        yield [FormField::addColumn(propertySuffix: 'foobar'), ['ea_form_column_foobar']];
        yield [FormField::addFieldset(propertySuffix: 'foobar'), ['ea_form_fieldset_foobar', 'ea_form_fieldset_close_foobar'], true];
        yield [FormField::addColumn(propertySuffix: 'foobar'), ['ea_form_column_foobar', 'ea_form_column_close_foobar'], true];
    }

    private function createEntityDto(FieldCollection $fieldCollection): EntityDto
    {
        $entityDto = new EntityDto(\stdClass::class, $this->createStub(ClassMetadata::class));
        $entityDto->setFields($fieldCollection);

        return $entityDto;
    }
}
