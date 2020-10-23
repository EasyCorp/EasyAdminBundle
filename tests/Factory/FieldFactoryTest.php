<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FieldFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormPanelType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormTabType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormGroupType;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use PHPUnit\Framework\TestCase;

/**
 * In order, tests that:
 * - the first missing panel is added
 * - the first missing tab is added
 * - the first missing group is added
 * - all missing decorators are added
 * - empty decorators are removed
 * - remove a decorator removes all its children in cascade (cascading removal)
 * - on index page, the decorators are removed
 * - hierarchized fields match with fields
 */
class FieldFactoryTest extends TestCase
{
    protected $crudDto;
    protected $fieldFactory;
    protected $entityDto;

    protected function setUp(): void
    {
        // CrudDto mock
        $this->crudDto = $this->getMockBuilder(CrudDto::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentPage'])
            ->getMock();


        // AdminContext mock
        $adminContext = $this->getMockBuilder(AdminContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCrud'])
            ->getMock();
        $adminContext
            ->expects($this->any())
            ->method('getCrud')
            ->willReturn($this->crudDto);


        // AdminContextProvider mock
        $adminContextProvider = $this->getMockBuilder(AdminContextProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContext'])
            ->getMock();
        $adminContextProvider->method('getContext')->willReturn($adminContext);


        // AuthorizationCheckerInterface mock
        $authorizationChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isGranted'])
            ->getMock();
        $authorizationChecker->method('isGranted')->willReturn(true);


        // simple FieldFactory
        $this->fieldFactory = new FieldFactory($adminContextProvider, $authorizationChecker, []);


        // simple EntityDto
        $metadata = new ClassMetadata('FakeEntity');
        $metadata->identifier = ['id'];
        $this->entityDto = new EntityDto('FakeEntity', $metadata);
    }

    public function testTheFirstMissingPanelIsAdded()
    {
        $this->crudDto->method('getCurrentPage')->willReturn(Crud::PAGE_NEW);

        $this->fieldFactory->processFields($this->entityDto, FieldCollection::new(['id']));

        $firstField = $this->entityDto->getFields()->first();

        $this->assertEquals(FormField::class, $firstField->getFieldFqcn());
    }

    public function testTheFirstMissingTabIsAdded()
    {
        $this->crudDto->method('getCurrentPage')->willReturn(Crud::PAGE_NEW);

        $this->fieldFactory->processFields($this->entityDto, FieldCollection::new([
            FormField::addPanel()->setProperty('MyPanel'),
                // <-- a tab is missing here
                FormField::addGroup()->setProperty('MyGroup'),
                    'id',
                FormField::addTab()->setProperty('My2ndTab'),
                    FormField::addGroup()->setProperty('My2ndGroup'),
                        'name',
        ]));

        $fields = $this->entityDto->getFields();

        // Expected: the 6 fields + 1 added tab = 7
        $this->assertEquals(7, count($fields));

        // Expected: the new tab is added in 2nd position, between its parent panel and its child group
        $it = $fields->getIterator();

        // -> The parent panel
        $this->assertEquals(EaFormPanelType::class, $it->current()->getFormType());
        $this->assertEquals('MyPanel', $it->key());
        $it->next();

        // -> The new tab
        $this->assertEquals(EaFormTabType::class, $it->current()->getFormType());
        $this->assertNotEquals('My2ndTab', $it->key());
        $it->next();

        // -> The child group
        $this->assertEquals(EaFormGroupType::class, $it->current()->getFormType());
        $this->assertEquals('MyGroup', $it->key());
    }

    public function testTheFirstMissingGroupIsAdded()
    {
        $this->crudDto->method('getCurrentPage')->willReturn(Crud::PAGE_NEW);

        $this->fieldFactory->processFields($this->entityDto, FieldCollection::new([
            FormField::addPanel()->setProperty('MyPanel'),
                FormField::addTab()->setProperty('MyTab'),
                    // <-- a group is missing here
                    'id',
                FormField::addTab()->setProperty('My2ndTab'),
                    FormField::addGroup()->setProperty('My2ndGroup'),
                        'name',
        ]));

        $fields = $this->entityDto->getFields();

        // Expected: the 6 fields + 1 added tab = 7
        $this->assertEquals(7, count($fields));

        // Expected: the new group is added in 3rd position, between its parent tab and its child field
        $it = $fields->getIterator();
        $it->next();

        // -> The parent tab
        $this->assertEquals(EaFormTabType::class, $it->current()->getFormType());
        $this->assertEquals('MyTab', $it->key());
        $it->next();

        // -> The new group
        $this->assertEquals(EaFormGroupType::class, $it->current()->getFormType());
        $this->assertNotEquals('My2ndGroup', $it->key());
        $it->next();

        // -> The child field
        $this->assertEquals(Field::class, $it->current()->getFieldFqcn());
        $this->assertEquals('id', $it->key());
    }

    public function testAllMissingDecoratorsAreAdded()
    {
        $this->crudDto->method('getCurrentPage')->willReturn(Crud::PAGE_NEW);

        $this->fieldFactory->processFields($this->entityDto, FieldCollection::new([
            // <-- a panel, a tab and a group are missing here
            'id',
            FormField::addPanel()->setProperty('MyPanel'),
            // <-- a tab and a group are missing here
            'name',
        ]));

        $fields = $this->entityDto->getFields();

        // Expected: the 3 fields + 5 added decorators = 8
        $this->assertEquals(8, count($fields));

        // We check all the 8 fields
        $it = $fields->getIterator();

        // A new panel
        $this->assertEquals(EaFormPanelType::class, $it->current()->getFormType());
        $it->next();

        // A new tab
        $this->assertEquals(EaFormTabType::class, $it->current()->getFormType());
        $it->next();

        // A new group
        $this->assertEquals(EaFormGroupType::class, $it->current()->getFormType());
        $it->next();

        // The 'id' field
        $this->assertEquals(Field::class, $it->current()->getFieldFqcn());
        $this->assertEquals('id', $it->key());
        $it->next();

        // The 'MyPanel' panel
        $this->assertEquals(EaFormPanelType::class, $it->current()->getFormType());
        $this->assertEquals('MyPanel', $it->key());
        $it->next();

        // A new tab
        $this->assertEquals(EaFormTabType::class, $it->current()->getFormType());
        $it->next();

        // A new group
        $this->assertEquals(EaFormGroupType::class, $it->current()->getFormType());
        $it->next();

        // The 'name' field
        $this->assertEquals(Field::class, $it->current()->getFieldFqcn());
        $this->assertEquals('name', $it->key());
    }

    public function testEmptyDecoratorsAreRemoved()
    {
        $this->crudDto->method('getCurrentPage')->willReturn(Crud::PAGE_NEW);

        $this->fieldFactory->processFields($this->entityDto, FieldCollection::new([
            FormField::addPanel(),
                FormField::addTab(),
                    FormField::addGroup(),
                        'id',
                    FormField::addGroup(),                       // <-- should be removed (because empty)
                        Field::new('name')->onlyOnIndex(),       // <-- should be removed (because we are on PAGE_NEW)
                FormField::addTab(),                             // <-- should be removed (because empty)
                    FormField::addGroup(),                       // <-- should be removed (because empty)
                        Field::new('birthdate')->onlyOnDetail(), // <-- should be removed (because we are on PAGE_NEW)
            FormField::addPanel(),                               // <-- should be removed (because empty)
                FormField::addTab(),                             // <-- should be removed (because empty)
        ]));

        $fields = $this->entityDto->getFields();

        // Expected: the remaining 4 fields (the 'id' field + its 3 decorators)
        $this->assertEquals(4, count($fields));

        $it = $fields->getIterator();

        $this->assertEquals(EaFormPanelType::class, $it->current()->getFormType());
        $it->next();

        $this->assertEquals(EaFormTabType::class, $it->current()->getFormType());
        $it->next();

        $this->assertEquals(EaFormGroupType::class, $it->current()->getFormType());
        $it->next();

        $this->assertEquals(Field::class, $it->current()->getFieldFqcn());
        $this->assertEquals('id', $it->key());
    }

    /**
     * Test that remove a decorator removes all its children in cascade.
     */
    public function testCascadingRemoval()
    {
        $this->crudDto->method('getCurrentPage')->willReturn(Crud::PAGE_NEW);

        $this->fieldFactory->processFields($this->entityDto, FieldCollection::new([
            FormField::addPanel(),
                FormField::addTab()->setProperty('RemovedTab')
                                   ->onlyOnDetail(), // <-- should be removed (because we are on PAGE_NEW)
                    FormField::addGroup(),           // <-- should be removed in cascade
                        'id',                        // <-- should be removed in cascade
                    FormField::addGroup(),           // <-- should be removed in cascade
                        'name',                      // <-- should be removed in cascade
                FormField::addTab()->setProperty('StayingTab'),
                    FormField::addGroup(),
                        'birthdate',
            FormField::addPanel()->onlyOnDetail(),  // <-- should be removed (because we are on PAGE_NEW)
                FormField::addTab(),                // <-- should be removed in cascade
                    'address', 'phone',             // <-- should be removed in cascade
        ]));

        $fields = $this->entityDto->getFields();

        // Expected: the remaining 4 fields
        $this->assertEquals(4, count($fields));

        $this->assertFalse($fields->offsetExists('RemovedTab'));
        $this->assertFalse($fields->offsetExists('id'));
        $this->assertFalse($fields->offsetExists('name'));
        $this->assertFalse($fields->offsetExists('address'));
        $this->assertFalse($fields->offsetExists('phone'));

        $it = $fields->getIterator();

        $this->assertEquals(EaFormPanelType::class, $it->current()->getFormType());
        $it->next();

        $this->assertEquals(EaFormTabType::class, $it->current()->getFormType());
        $this->assertEquals('StayingTab', $it->key());
        $it->next();

        $this->assertEquals(EaFormGroupType::class, $it->current()->getFormType());
        $it->next();

        $this->assertEquals(Field::class, $it->current()->getFieldFqcn());
        $this->assertEquals('birthdate', $it->key());
    }

    public function testOnIndexPageDecoratorsAreRemoved()
    {
        $this->crudDto->method('getCurrentPage')->willReturn(Crud::PAGE_INDEX);

        $this->fieldFactory->processFields($this->entityDto, FieldCollection::new([
            FormField::addPanel(),
                FormField::addTab()->onlyOnIndex(), // <-- we don't keep this tab, even with onlyOnIndex()
                    FormField::addGroup(),
                        'id',
                    FormField::addGroup(),
                        'name',
                FormField::addTab(),
                    FormField::addGroup(),
                        'birthdate',
            FormField::addPanel(),
                FormField::addTab(),
                    'address', 'phone',
        ]));

        $fields = $this->entityDto->getFields();

        // Expected: the remaining 5 fields
        $this->assertEquals(5, count($fields));

        // Expected: no remaining decorator
        foreach ($fields as $key => $field) {
            $this->assertNotEquals(FormField::class, $field->getFieldFqcn());
        }
    }

    public function testHierarchizedFieldsMatchWithFields()
    {
        $this->crudDto->method('getCurrentPage')->willReturn(Crud::PAGE_NEW);

        $this->fieldFactory->processFields($this->entityDto, FieldCollection::new([
            FormField::addPanel(),
                FormField::addTab(),
                    FormField::addGroup(),
                        'id',
                    FormField::addGroup(),
                        'name',
                FormField::addTab(),
                    FormField::addGroup(),
                        'birthdate',
            FormField::addPanel(),
                FormField::addTab(),
                    FormField::addGroup(),
                    'address', 'phone',
        ]));

        $flatFields = $this->entityDto->getFields()->getIterator();
        $hierarchizedFields = $this->entityDto->getHierarchizedFields();

        foreach ($hierarchizedFields as $panel) {
            $this->assertEquals($flatFields->current(), $panel['field']);
            $flatFields->next();

            foreach ($panel['tabs'] as $tab) {
                $this->assertEquals($flatFields->current(), $tab['field']);
                $flatFields->next();

                foreach ($tab['groups'] as $group) {
                    $this->assertEquals($flatFields->current(), $group['field']);
                    $flatFields->next();

                    foreach ($group['fields'] as $field) {
                        $this->assertEquals($flatFields->current(), $field);
                        $this->assertEquals($panel['field'], $field->getDecorator('panel'));
                        $this->assertEquals($tab['field'],   $field->getDecorator('tab'));
                        $this->assertEquals($group['field'], $field->getDecorator('group'));
                        $flatFields->next();
                    }
                }
            }
        }

        // At this point, all fields in $flatFields should be consumed
        // ie, when we finish to process the hierarchized fields, there are also no "flat fields" anymore
        $this->assertNull($flatFields->current());
    }
}
