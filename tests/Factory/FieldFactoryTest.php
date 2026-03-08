<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormLayoutFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\FieldFactory\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FieldFactoryTest extends KernelTestCase
{
    private EntityDto $projectDto;
    private FieldFactory $fieldFactory;

    protected function setUp(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->projectDto = new EntityDto(Project::class, $entityManager->getClassMetadata(Project::class));

        $authorizationCheckerInterface = $this->getMockBuilder(AuthorizationCheckerInterface::class)->disableOriginalConstructor()->getMock();
        $authorizationCheckerInterface->method('isGranted')->willReturn(true);

        $this->fieldFactory = new FieldFactory(
            $this->getMockBuilder(AdminContextProviderInterface::class)->disableOriginalConstructor()->getMock(),
            $authorizationCheckerInterface,
            [],
            new FormLayoutFactory(),
        );
    }

    public function testEmpty(): void
    {
        $fieldCollection = FieldCollection::new([]);

        $this->fieldFactory->processFields($this->projectDto, $fieldCollection, Crud::PAGE_INDEX);

        $this->assertEmpty($fieldCollection);
    }

    /**
     * @dataProvider removesFieldsOnPage
     */
    public function testRemovesFieldsOnPage(string $pageName, FieldInterface $field): void
    {
        $fieldCollection = FieldCollection::new([$field]);

        $this->fieldFactory->processFields($this->projectDto, $fieldCollection, $pageName);

        $this->assertEmpty($fieldCollection);
    }

    public static function removesFieldsOnPage(): \Generator
    {
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->hideOnIndex()];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->onlyOnDetail()];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->onlyOnForms()];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->onlyWhenCreating()];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->onlyWhenUpdating()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->hideOnDetail()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->onlyOnIndex()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->onlyOnForms()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->onlyWhenCreating()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->onlyWhenUpdating()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->hideOnForm()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->hideWhenUpdating()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->onlyOnDetail()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->onlyOnIndex()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->onlyWhenCreating()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->hideOnForm()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->hideWhenCreating()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->onlyOnDetail()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->onlyOnIndex()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->onlyWhenUpdating()];
    }

    /**
     * @dataProvider keepsFieldsOnPage
     */
    public function testKeepsFieldsOnPage(string $pageName, FieldInterface $field): void
    {
        $fieldCollection = FieldCollection::new([$field]);

        $this->fieldFactory->processFields($this->projectDto, $fieldCollection, $pageName);

        // Remove layout fields
        foreach ($fieldCollection as $field) {
            if (\in_array($field->getProperty(), ['ea_form_fieldset', 'ea_form_fieldset_close'])) {
                $fieldCollection->unset($field);
            }
        }

        $this->assertCount(1, $fieldCollection);
    }

    public static function keepsFieldsOnPage(): \Generator
    {
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->onlyOnIndex()];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->hideOnDetail()];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->hideOnForm()];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->hideWhenUpdating()];
        yield [Crud::PAGE_INDEX, Field\TextField::new('name')->hideWhenCreating()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->onlyOnDetail()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->hideOnIndex()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->hideOnForm()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->hideWhenUpdating()];
        yield [Crud::PAGE_DETAIL, Field\TextField::new('name')->hideWhenCreating()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->onlyOnForms()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->onlyWhenUpdating()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->hideOnIndex()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->hideOnDetail()];
        yield [Crud::PAGE_EDIT, Field\TextField::new('name')->hideWhenCreating()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->onlyOnForms()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->onlyWhenCreating()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->hideOnIndex()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->hideOnDetail()];
        yield [Crud::PAGE_NEW, Field\TextField::new('name')->hideWhenUpdating()];
    }

    /**
     * @dataProvider stringNames
     */
    public function testStringNamesAreTurnedIntoFields(string $property, string $expectedField): void
    {
        $fieldCollection = FieldCollection::new([$property]);

        $this->fieldFactory->processFields($this->projectDto, $fieldCollection, Crud::PAGE_INDEX);

        $this->assertSame(
            $expectedField,
            array_values(array_map(fn (FieldDto $field) => $field->getFieldFqcn(), (array) $fieldCollection->getIterator()))[0],
            sprintf('Failed asserting that string "%s" is turned into a field of type "%s".', $property, $expectedField),
        );
    }

    public static function stringNames(): \Generator
    {
        yield ['rolesJson', Field\ArrayField::class];
        yield ['statesSimpleArray', Field\ArrayField::class];
        yield ['internal', Field\BooleanField::class];
        yield ['startDateMutable', Field\DateField::class];
        yield ['startDateImmutable', Field\DateField::class];
        yield ['startDateTimeMutable', Field\DateTimeField::class];
        yield ['startDateTimeImmutable', Field\DateTimeField::class];
        yield ['startDateTimeTzMutable', Field\DateTimeField::class];
        yield ['startDateTimeTzImmutable', Field\DateTimeField::class];
        yield ['id', Field\IdField::class];
        yield ['countInteger', Field\IntegerField::class];
        yield ['countSmallint', Field\IntegerField::class];
        yield ['priceDecimal', Field\NumberField::class];
        yield ['priceFloat', Field\NumberField::class];
        yield ['description', Field\TextareaField::class];
        yield ['name', Field\TextField::class];
        yield ['startTimeMutable', Field\TimeField::class];
        yield ['startTimeImmutable', Field\TimeField::class];
    }
}
