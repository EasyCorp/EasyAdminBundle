<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FormLayoutFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\DoctrineTypesTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\IdentityTranslator;

class FieldFactoryTest extends KernelTestCase
{
    private EntityDto $categoryDto;
    private FieldFactory $fieldFactory;

    protected function setUp(): void
    {
        $this->categoryDto = $this->createEntityDto(Category::class);

        $authorizationCheckerInterface = $this->getMockBuilder(AuthorizationCheckerInterface::class)->disableOriginalConstructor()->getMock();
        $authorizationCheckerInterface->method('isGranted')->willReturn(true);

        $this->fieldFactory = new FieldFactory(
            $this->getMockBuilder(AdminContextProviderInterface::class)->disableOriginalConstructor()->getMock(),
            $authorizationCheckerInterface,
            [],
            new FormLayoutFactory(new IdentityTranslator()),
        );
    }

    public function testEmpty(): void
    {
        $fieldCollection = new FieldCollection([]);

        $this->fieldFactory->processFields($this->categoryDto, $fieldCollection, Crud::PAGE_INDEX);

        $this->assertEmpty($fieldCollection);
    }

    #[DataProvider('removesFieldsOnPage')]
    public function testRemovesFieldsOnPage(string $pageName, FieldInterface $field): void
    {
        $fieldCollection = new FieldCollection([$field]);

        $this->fieldFactory->processFields($this->categoryDto, $fieldCollection, $pageName);

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

    #[DataProvider('keepsFieldsOnPage')]
    public function testKeepsFieldsOnPage(string $pageName, FieldInterface $field): void
    {
        $fieldCollection = new FieldCollection([$field]);

        $this->fieldFactory->processFields($this->categoryDto, $fieldCollection, $pageName);

        // remove layout fields
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

    #[DataProvider('stringNames')]
    public function testStringNamesAreTurnedIntoFields(string $entityFqcn, string $property, string $expectedField): void
    {
        $fieldCollection = new FieldCollection([$property]);

        $this->fieldFactory->processFields($this->createEntityDto($entityFqcn), $fieldCollection, Crud::PAGE_INDEX);

        $this->assertSame(
            $expectedField,
            array_values(array_map(static fn (FieldDto $field) => $field->getFieldFqcn(), (array) $fieldCollection->getIterator()))[0],
            sprintf('Failed asserting that string "%s" is turned into a field of type "%s".', $property, $expectedField),
        );
    }

    public static function stringNames(): \Generator
    {
        yield [DoctrineTypesTestEntity::class, 'jsonColumn', Field\ArrayField::class];
        yield [DoctrineTypesTestEntity::class, 'simpleArrayColumn', Field\ArrayField::class];
        yield [DoctrineTypesTestEntity::class, 'booleanColumn', Field\BooleanField::class];
        yield [DoctrineTypesTestEntity::class, 'dateMutableColumn', Field\DateField::class];
        yield [DoctrineTypesTestEntity::class, 'dateImmutableColumn', Field\DateField::class];
        yield [DoctrineTypesTestEntity::class, 'dateTimeMutableColumn', Field\DateTimeField::class];
        yield [DoctrineTypesTestEntity::class, 'dateTimeImmutableColumn', Field\DateTimeField::class];
        yield [DoctrineTypesTestEntity::class, 'dateTimeTzMutableColumn', Field\DateTimeField::class];
        yield [DoctrineTypesTestEntity::class, 'dateTimeTzImmutableColumn', Field\DateTimeField::class];
        yield [DoctrineTypesTestEntity::class, 'id', Field\IdField::class];
        yield [DoctrineTypesTestEntity::class, 'integerColumn', Field\IntegerField::class];
        yield [DoctrineTypesTestEntity::class, 'smallintColumn', Field\IntegerField::class];
        yield [DoctrineTypesTestEntity::class, 'decimalColumn', Field\NumberField::class];
        yield [DoctrineTypesTestEntity::class, 'floatColumn', Field\NumberField::class];
        yield [DoctrineTypesTestEntity::class, 'textColumn', Field\TextareaField::class];
        yield [DoctrineTypesTestEntity::class, 'stringColumn', Field\TextField::class];
        yield [DoctrineTypesTestEntity::class, 'timeMutableColumn', Field\TimeField::class];
        yield [DoctrineTypesTestEntity::class, 'timeImmutableColumn', Field\TimeField::class];
        yield [DoctrineTypesTestEntity::class, 'address.street', Field\IntegerField::class];
        yield [DoctrineTypesTestEntity::class, 'address.city', Field\TextField::class];
        yield [BlogPost::class, 'author', Field\AssociationField::class];
        yield [User::class, 'favoriteCategory', Field\AssociationField::class];
        yield [User::class, 'blogPosts', Field\CollectionField::class];
        yield [Category::class, 'blogPosts', Field\AssociationField::class];
        yield [BlogPost::class, 'categories', Field\AssociationField::class];
    }

    /**
     * @param array<FieldInterface> $fields
     * @param array<string>         $expectedProperties
     */
    #[DataProvider('fieldsInsideDeniedLayoutContainers')]
    public function testRemovesFieldsInsideDeniedLayoutContainers(string $pageName, array $fields, array $expectedProperties): void
    {
        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturnCallback(
            static fn (mixed $attribute, mixed $fieldDto): bool => 'ROLE_ADMIN' !== $fieldDto->getPermission()
        );
        $fieldFactory = new FieldFactory(
            $this->createStub(AdminContextProviderInterface::class),
            $authorizationChecker,
            [],
            new FormLayoutFactory(new IdentityTranslator()),
        );
        $fieldCollection = new FieldCollection($fields);

        $fieldFactory->processFields($this->categoryDto, $fieldCollection, $pageName);

        $properties = [];
        foreach ($fieldCollection as $fieldDto) {
            if (!$fieldDto->isFormLayoutField()) {
                $properties[] = $fieldDto->getProperty();
            }
        }
        $this->assertSame($expectedProperties, $properties);
    }

    public static function fieldsInsideDeniedLayoutContainers(): \Generator
    {
        foreach ([Crud::PAGE_INDEX, Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW] as $pageName) {
            yield 'tabs on '.$pageName => [$pageName, [
                Field\FormField::addTab('Tab 1')->setPermission('ROLE_ADMIN'),
                Field\TextField::new('field1'),
                Field\FormField::addTab('Tab 2'),
                Field\TextField::new('field2'),
                Field\FormField::addTab('Tab 3')->setPermission('ROLE_ADMIN'),
                Field\TextField::new('field3'),
                Field\FormField::addColumn(),
                Field\FormField::addFieldset(),
                Field\TextField::new('field4'),
                Field\FormField::addTab('Tab 4'),
                Field\TextField::new('field5'),
            ], ['field2', 'field5']];

            yield 'columns on '.$pageName => [$pageName, [
                Field\FormField::addTab('Tab 1'),
                Field\FormField::addColumn(),
                Field\TextField::new('field1'),
                Field\FormField::addColumn()->setPermission('ROLE_ADMIN'),
                Field\TextField::new('field2'),
                Field\FormField::addFieldset(),
                Field\TextField::new('field3'),
                Field\FormField::addColumn(),
                Field\TextField::new('field4'),
                Field\FormField::addColumn()->setPermission('ROLE_ADMIN'),
                Field\TextField::new('field5'),
                Field\FormField::addTab('Tab 2'),
                Field\TextField::new('field6'),
            ], ['field1', 'field4', 'field6']];

            yield 'fieldsets on '.$pageName => [$pageName, [
                Field\FormField::addColumn(),
                Field\FormField::addFieldset(),
                Field\TextField::new('field1'),
                Field\FormField::addFieldset()->setPermission('ROLE_ADMIN'),
                Field\TextField::new('field2'),
                Field\FormField::addRow(),
                Field\TextField::new('field3'),
                Field\FormField::addFieldset(),
                Field\TextField::new('field4'),
                Field\FormField::addFieldset()->setPermission('ROLE_ADMIN'),
                Field\TextField::new('field5'),
                Field\FormField::addColumn(),
                Field\TextField::new('field6'),
            ], ['field1', 'field4', 'field6']];
        }
    }

    #[DataProvider('utcDateTimeTypes')]
    public function testUtcDateTimeTypesAreTurnedIntoDateTimeFields(string $doctrineType): void
    {
        // Doctrine ORM 2.x uses arrays, Doctrine ORM 3.x uses FieldMapping objects
        $fieldMapping = class_exists(FieldMapping::class)
            ? new FieldMapping($doctrineType, 'createdAt', 'created_at')
            : ['fieldName' => 'createdAt', 'type' => $doctrineType, 'columnName' => 'created_at'];

        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $classMetadata->method('hasAssociation')->willReturn(false);
        $classMetadata->method('getFieldMapping')->willReturn($fieldMapping);
        $classMetadata->fieldMappings = ['createdAt' => $fieldMapping];

        $fieldCollection = new FieldCollection(['createdAt']);

        $this->fieldFactory->processFields(new EntityDto(\stdClass::class, $classMetadata), $fieldCollection, Crud::PAGE_INDEX);

        $this->assertSame(Field\DateTimeField::class, $fieldCollection->getByProperty('createdAt')?->getFieldFqcn());
    }

    /**
     * The metadata of these types is stubbed (instead of adding columns to a test entity)
     * because they need Doctrine DBAL 4.5 and the test suite also runs with older versions.
     */
    public static function utcDateTimeTypes(): \Generator
    {
        yield ['datetime_utc'];
        yield ['datetime_utc_immutable'];
    }

    /**
     * @param class-string $entityFqcn
     */
    private function createEntityDto(string $entityFqcn): EntityDto
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        return new EntityDto($entityFqcn, $entityManager->getClassMetadata($entityFqcn));
    }
}
