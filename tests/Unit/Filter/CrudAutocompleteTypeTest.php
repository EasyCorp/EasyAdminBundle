<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
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
            ->with(Project::class)
            ->willReturn($this->repository);
        $this->entityManager
            ->method('getClassMetadata')
            ->with(Project::class)
            ->willReturn($this->classMetadata);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $registry = $this->createStub(Registry::class);
        $registry
            ->method('getManagerForClass')
            ->with(Project::class)
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
        $project = (new Project())->setId(123)->setName('Foo');

        $this->entityManager
            ->method('contains')
            ->with($project)
            ->willReturn(true);
        $this->repository
            ->method('findBy')
            ->willReturn([$project]);
        $this->classMetadata
            ->method('getIdentifierValues')
            ->with($project)
            ->willReturn(['id' => $project->getId()]);

        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Project::class,
        ]);
        $form->submit(['autocomplete' => '123']);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($project, $form->getData());

        $view = $form->createView();

        $this->assertArrayHasKey('autocomplete', $view->children);
        $this->assertEquals(
            ['123' => new ChoiceView($project, 123, 'Foo')],
            $view->children['autocomplete']->vars['choices'],
        );
    }

    public function testSubmitValidMultipleData(): void
    {
        $project1 = (new Project())->setId(123)->setName('Foo');

        $this->entityManager
            ->method('contains')
            ->with($project1)
            ->willReturn(true);
        $this->repository
            ->method('findBy')
            ->withAnyParameters()
            ->willReturn([$project1]);
        $this->classMetadata
            ->method('getIdentifierValues')
            ->with($project1)
            ->willReturn(['id' => $project1->getId()]);

        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Project::class,
            'multiple' => true,
        ]);
        $form->submit(['autocomplete' => ['123']]);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(new ArrayCollection([$project1]), $form->getData());
    }

    public function testSubmitEmptySingleData(): void
    {
        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Project::class,
        ]);
        $form->submit(['autocomplete' => '']);

        $this->assertTrue($form->isSynchronized());
        $this->assertNull($form->getData());
    }

    public function testSubmitEmptyMultipleData(): void
    {
        $form = $this->factory->create(CrudAutocompleteType::class, null, [
            'class' => Project::class,
            'multiple' => true,
        ]);
        $form->submit(null);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(new ArrayCollection(), $form->getData());
    }
}
