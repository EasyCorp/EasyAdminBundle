<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Twig\Environment;

class CrudAutocompleteTypeTest extends TypeTestCase
{
    private EntityRepository $repository;
    private ClassMetadata $classMetadata;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(EntityRepository::class);

        $this->classMetadata = $this->createStub(ClassMetadata::class);
        $this->classMetadata
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $this->classMetadata
            ->method('getTypeOfField')
            ->willReturn('integer');

        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->entityManager
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->repository);
        $this->entityManager
            ->method('getClassMetadata')
            ->with(Category::class)
            ->willReturn($this->classMetadata);

        parent::setUp();
    }

    protected function getExtensions(?ViolationMapperInterface $violationMapper = null): array
    {
        $registry = $this->createStub(Registry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($this->entityManager)
        ;

        $twig = $this->createStub(Environment::class);

        return [
            new PreloadedExtension(
                [
                    'entity' => new EntityType($registry),
                    'easyadmin_autocomplete' => new CrudAutocompleteType($twig),
                ],
                [],
            ),
        ];
    }

    public function testSubmitValidSingleData(): void
    {
        $category = (new Category())->setName('Category 1');

        $this->entityManager
            ->method('contains')
            ->with($category)
            ->willReturn(true);
        $this->repository
            ->method('findBy')
            ->willReturn([$category]);
        $this->classMetadata
            ->method('getIdentifierValues')
            ->with($category)
            ->willReturn(['id' => 123]);

        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
        ]);
        $form->submit(['autocomplete' => '123']);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($category, $form->getData());

        $view = $form->createView();

        $this->assertArrayHasKey('autocomplete', $view->children);
        $this->assertEquals(
            ['123' => new ChoiceView($category, 123, 'Category 1')],
            $view->children['autocomplete']->vars['choices'],
        );
    }

    public function testSubmitValidMultipleData(): void
    {
        $category = (new Category())->setName('Category 1');

        $this->entityManager
            ->method('contains')
            ->with($category)
            ->willReturn(true);
        $this->repository
            ->method('findBy')
            ->withAnyParameters()
            ->willReturn([$category]);
        $this->classMetadata
            ->method('getIdentifierValues')
            ->with($category)
            ->willReturn(['id' => 123]);

        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
            'multiple' => true,
        ]);
        $form->submit(['autocomplete' => ['123']]);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(new ArrayCollection([$category]), $form->getData());
    }

    public function testSubmitEmptySingleData(): void
    {
        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
        ]);
        $form->submit(['autocomplete' => '']);

        $this->assertTrue($form->isSynchronized());
        $this->assertNull($form->getData());
    }

    public function testSubmitEmptyMultipleData(): void
    {
        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
            'multiple' => true,
        ]);
        $form->submit(null);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(new ArrayCollection(), $form->getData());
    }

    public function testCustomChoiceLabelIsPreserved(): void
    {
        $category = (new Category())->setName('Category 1');

        $this->entityManager
            ->method('contains')
            ->with($category)
            ->willReturn(true);
        $this->repository
            ->method('findBy')
            ->willReturn([$category]);
        $this->classMetadata
            ->method('getIdentifierValues')
            ->with($category)
            ->willReturn(['id' => 123]);

        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
            'choice_label' => static fn (Category $category): string => 'Custom: '.$category->getName(),
        ]);
        $form->submit(['autocomplete' => '123']);

        $this->assertTrue($form->isSynchronized());

        $view = $form->createView();

        // the user-supplied callable is used to build the option label,
        // overriding the default Category::__toString() output
        $this->assertEquals(
            ['123' => new ChoiceView($category, 123, 'Custom: Category 1')],
            $view->children['autocomplete']->vars['choices'],
        );
    }

    public function testAutocompleteCallbackStillWinsWhenChoiceLabelIsNotSet(): void
    {
        $category = (new Category())->setName('Category 1');

        $this->entityManager
            ->method('contains')
            ->with($category)
            ->willReturn(true);
        $this->repository
            ->method('findBy')
            ->willReturn([$category]);
        $this->classMetadata
            ->method('getIdentifierValues')
            ->with($category)
            ->willReturn(['id' => 123]);

        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
            'autocomplete_callback' => static fn (Category $category): string => 'cb:'.$category->getName(),
        ]);
        $form->submit(['autocomplete' => '123']);

        $view = $form->createView();

        $this->assertEquals(
            ['123' => new ChoiceView($category, 123, 'cb:Category 1')],
            $view->children['autocomplete']->vars['choices'],
        );
    }

    public function testChoiceLabelOptionRejectsInvalidType(): void
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException::class);

        $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
            'choice_label' => 42, // int is not in the allowed types
        ]);
    }

    public function testGroupByCallableIsForwardedToTheInnerEntityType(): void
    {
        $category = (new Category())->setName('Category 1');

        $this->entityManager
            ->method('contains')
            ->with($category)
            ->willReturn(true);
        $this->repository
            ->method('findBy')
            ->willReturn([$category]);
        $this->classMetadata
            ->method('getIdentifierValues')
            ->with($category)
            ->willReturn(['id' => 123]);

        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
            'group_by' => static fn (Category $category): string => 'Group A',
        ]);
        $form->submit(['autocomplete' => '123']);

        $this->assertTrue($form->isSynchronized());

        $view = $form->createView();

        // the selected choice is rendered inside an <optgroup> named after the group
        $this->assertEquals(
            ['Group A' => new ChoiceGroupView('Group A', ['123' => new ChoiceView($category, 123, 'Category 1')])],
            $view->children['autocomplete']->vars['choices'],
        );
    }

    public function testGroupByPropertyPathIsForwardedToTheInnerEntityType(): void
    {
        $category = (new Category())->setName('Category 1');

        $this->entityManager
            ->method('contains')
            ->with($category)
            ->willReturn(true);
        $this->repository
            ->method('findBy')
            ->willReturn([$category]);
        $this->classMetadata
            ->method('getIdentifierValues')
            ->with($category)
            ->willReturn(['id' => 123]);

        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
            'group_by' => 'name',
        ]);
        $form->submit(['autocomplete' => '123']);

        $view = $form->createView();

        $this->assertEquals(
            ['Category 1' => new ChoiceGroupView('Category 1', ['123' => new ChoiceView($category, 123, 'Category 1')])],
            $view->children['autocomplete']->vars['choices'],
        );
    }

    public function testGroupByOptionRejectsInvalidType(): void
    {
        $this->expectException(\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException::class);

        $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Category::class,
            'group_by' => 42, // int is not in the allowed types
        ]);
    }
}
