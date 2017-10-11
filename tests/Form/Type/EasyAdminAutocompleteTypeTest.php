<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Type;

use AppTestBundle\Entity\UnitTests\Category;
use Doctrine\Common\Collections\ArrayCollection;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EasyAdminAutocompleteType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class EasyAdminAutocompleteTypeTest extends TypeTestCase
{
    const ENTITY_CLASS = 'AppTestBundle\Entity\UnitTests\Category';

    private $doctrine;
    private $entityManager;
    private $classMetadata;
    private $repository;
    private $configManager;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->classMetadata = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->classMetadata
            ->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(array('id'));
        $this->classMetadata
            ->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn('integer');

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->repository);
        $this->entityManager
            ->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->classMetadata);

        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine
            ->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->entityManager);

        $this->configManager = $this->getMockBuilder('EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager
            ->expects($this->any())
            ->method('getEntityConfigByClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(array('name' => 'Category'));

        parent::setUp();
    }

    protected function getExtensions()
    {
        $types = array(
            'entity' => new EntityType($this->doctrine),
            'easyadmin_autocomplete' => new EasyAdminAutocompleteType($this->configManager),
        );

        return array(
            new PreloadedExtension($types, array()),
        );
    }

    public function testSubmitValidSingleData()
    {
        $category = new Category();
        $category->id = 1;

        $this->entityManager
            ->expects($this->any())
            ->method('contains')
            ->with($category)
            ->willReturn(true);

        $this->repository
            ->expects($this->any())
            ->method('findBy')
            ->willReturn(array($category));

        $this->classMetadata
            ->expects($this->any())
            ->method('getIdentifierValues')
            ->with($category)
            ->willReturn(array('id' => $category->id));

        $form = $this->factory->create(LegacyFormHelper::getType('easyadmin_autocomplete'), null, array(
            'class' => self::ENTITY_CLASS,
        ));
        $formData = array('autocomplete' => '1');
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($category, $form->getData());

        $view = $form->createView();
        $children = $view->children;
        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }

        if (class_exists('Symfony\Component\Form\ChoiceList\View\ChoiceView', false)) {
            $choiceView = new \Symfony\Component\Form\ChoiceList\View\ChoiceView($category, 1, '1');
        } else {
            $choiceView = new \Symfony\Component\Form\Extension\Core\View\ChoiceView($category, 1, '1');
        }

        $this->assertEquals(array('1' => $choiceView), $children['autocomplete']->vars['choices']);
    }

    public function testSubmitValidMultipleData()
    {
        $category1 = new Category();
        $category1->id = 1;

        $this->entityManager
            ->expects($this->any())
            ->method('contains')
            ->with($category1)
            ->willReturn(true);

        $this->repository
            ->expects($this->any())
            ->method('findBy')
            ->withAnyParameters()
            ->willReturn(array($category1));

        $this->classMetadata
            ->expects($this->any())
            ->method('getIdentifierValues')
            ->with($category1)
            ->willReturn(array('id' => $category1->id));

        $form = $this->factory->create(LegacyFormHelper::getType('easyadmin_autocomplete'), null, array(
            'class' => self::ENTITY_CLASS,
            'multiple' => true,
        ));
        $form->submit(array('autocomplete' => array('1')));

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(new ArrayCollection(array($category1)), $form->getData());
    }

    public function testSubmitEmptySingleData()
    {
        $form = $this->factory->create(LegacyFormHelper::getType('easyadmin_autocomplete'), null, array(
            'class' => self::ENTITY_CLASS,
        ));
        $form->submit(array('autocomplete' => ''));

        $this->assertTrue($form->isSynchronized());
        $this->assertNull($form->getData());
    }

    public function testSubmitEmptyMultipleData()
    {
        $form = $this->factory->create(LegacyFormHelper::getType('easyadmin_autocomplete'), null, array(
            'class' => self::ENTITY_CLASS,
            'multiple' => true,
        ));
        $form->submit(null);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(new ArrayCollection(), $form->getData());
    }
}
