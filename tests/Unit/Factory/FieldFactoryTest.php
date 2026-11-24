<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Factory;

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
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\IdentityTranslator;

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
            new FormLayoutFactory(new IdentityTranslator()),
        );
    }

    public function testEmpty(): void
    {
        $fieldCollection = new FieldCollection([]);

        $this->fieldFactory->processFields($this->projectDto, $fieldCollection, Crud::PAGE_INDEX);

        $this->assertEmpty($fieldCollection);
    }

    /**
     * @dataProvider removesFieldsOnPage
     */
    public function testRemovesFieldsOnPage(string $pageName, FieldInterface $field): void
    {
        $fieldCollection = new FieldCollection([$field]);

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
        $fieldCollection = new FieldCollection([$field]);

        $this->fieldFactory->processFields($this->projectDto, $fieldCollection, $pageName);

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

    /**
     * @dataProvider fieldsInsideDeniedLayoutContainers
     *
     * @param array<FieldInterface> $fields
     * @param array<string>         $expectedProperties
     */
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

        $fieldFactory->processFields($this->projectDto, $fieldCollection, $pageName);

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

    /**
     * @dataProvider stringNames
     */
    public function testStringNamesAreTurnedIntoFields(string $property, string $expectedField): void
    {
        $fieldCollection = new FieldCollection([$property]);

        $this->fieldFactory->processFields($this->projectDto, $fieldCollection, Crud::PAGE_INDEX);

        $this->assertSame(
            $expectedField,
            array_values(array_map(static fn (FieldDto $field) => $field->getFieldFqcn(), (array) $fieldCollection->getIterator()))[0],
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
        yield ['price.amount', Field\IntegerField::class];
        yield ['price.currency', Field\TextField::class];
        yield ['latestRelease', Field\AssociationField::class];
        yield ['leadDeveloper', Field\AssociationField::class];
        yield ['projectIssues', Field\CollectionField::class];
        yield ['favouriteProjectOf', Field\AssociationField::class];
        yield ['projectTags', Field\AssociationField::class];
    }
}
