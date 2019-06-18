<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Bridge\Doctrine\Tests\Fixtures\SingleIntIdEntity;

class EntityFilterTypeTest extends FilterTypeTest
{
    /** @var EntityManager */
    private $em;
    /** @var ManagerRegistry */
    private $emRegistry;

    protected function setUp(): void
    {
        $this->em = DoctrineTestHelper::createTestEntityManager();
        $this->emRegistry = $this->createRegistryMock('default', $this->em);

        parent::setUp();

        // reset counter (only for test purpose)
        $m = new \ReflectionProperty(EntityFilterType::class, 'uniqueAliasId');
        $m->setAccessible(true);
        $m->setValue(0);

        $schemaTool = new SchemaTool($this->em);
        $classes = [
            $this->em->getClassMetadata(SingleIntIdEntity::class),
        ];

        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception $e) {
        }

        try {
            $schemaTool->createSchema($classes);
        } catch (\Exception $e) {
        }
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->em = null;
        $this->emRegistry = null;
    }

    /**
     * @dataProvider getDataProviderToOneAssoc
     */
    public function testSubmit($submittedData, $data, array $options)
    {
        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Bar');
        $this->persist([$entity1, $entity2]);

        $form = $this->factory->create(EntityFilterType::class, null, $options);
        $form->submit($submittedData);

        $this->assertEquals($data, $form->getData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    /**
     * @dataProvider getDataProviderToOneAssoc
     */
    public function testFilterToOneAssociationType($submittedData, $data, array $options)
    {
        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Bar');
        $this->persist([$entity1, $entity2]);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['o'])
        ;
        if (null === $data['value'] || ($options['value_type_options']['multiple'] && 0 === \count($data['value']))) {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with(\sprintf('o.foo %s', $data['comparison']))
                ->willReturn($qb)
            ;
        } else {
            $orX = new Expr\Orx();
            $orX->add(\sprintf('o.foo %s (:foo_1)', $data['comparison']));
            if (ComparisonType::NEQ === $data['comparison']) {
                $orX->add('o.foo IS NULL');
            }
            $qb->expects($this->once())
                ->method('andWhere')
                ->with($orX)
                ->willReturn($qb)
            ;
            $qb->expects($this->once())
                ->method('setParameter')
                ->with('foo_1', $data['value'])
            ;
        }

        $form = $this->factory->create(EntityFilterType::class, null, $options);
        $form->submit($submittedData);

        $filter = $this->filterRegistry->resolveType($form);
        $this->assertSame(EntityFilterType::class, \get_class($filter));

        $filter->filter($qb, $form, ['property' => 'foo', 'dataType' => 'association', 'associationType' => ClassMetadata::TO_ONE]);
    }

    /**
     * @dataProvider getDataProviderToManyAssoc
     */
    public function testFilterToManyAssociationType($submittedData, $data, array $options)
    {
        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Bar');
        $this->persist([$entity1, $entity2]);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['o'])
        ;
        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('o.foo', 'foo_2')
            ->willReturn($qb)
        ;
        if (0 === \count($data['value'])) {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with(\sprintf('foo_2 %s', $data['comparison']))
                ->willReturn($qb)
            ;
        } else {
            $orX = new Expr\Orx();
            $orX->add(\sprintf('foo_2 %s (:foo_1)', $data['comparison']));
            if ('NOT IN' === $data['comparison']) {
                $orX->add('foo_2 IS NULL');
            }
            $qb->expects($this->once())
                ->method('andWhere')
                ->with($orX)
                ->willReturn($qb)
            ;
            $qb->expects($this->once())
                ->method('setParameter')
                ->with('foo_1', $data['value'])
            ;
        }

        $form = $this->factory->create(EntityFilterType::class, null, $options);
        $form->submit($submittedData);

        $filter = $this->filterRegistry->resolveType($form);
        $this->assertSame(EntityFilterType::class, \get_class($filter));

        $filter->filter($qb, $form, ['property' => 'foo', 'dataType' => 'association', 'associationType' => ClassMetadata::TO_MANY]);
    }

    public function getDataProviderToOneAssoc(): iterable
    {
        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Bar');

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => 2],
            ['comparison' => '=', 'value' => $entity2],
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => false,
                ],
            ],
        ];

        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => 1],
            ['comparison' => '!=', 'value' => $entity1],
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => false,
                ],
            ],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => false,
                ],
            ],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => new ArrayCollection()],
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => true,
                ],
            ],
        ];
    }

    public function getDataProviderToManyAssoc(): iterable
    {
        $entity1 = new SingleIntIdEntity(1, 'Foo');
        $entity2 = new SingleIntIdEntity(2, 'Bar');

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => new ArrayCollection()],
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => true,
                ],
            ],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => [1, 2]],
            ['comparison' => 'IN', 'value' => new ArrayCollection([$entity1, $entity2])],
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => true,
                ],
            ],
        ];

        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => [1, 2]],
            ['comparison' => 'NOT IN', 'value' => new ArrayCollection([$entity1, $entity2])],
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => true,
                ],
            ],
        ];
    }

    protected function getExtensions()
    {
        return \array_merge(parent::getExtensions(), [
            new DoctrineOrmExtension($this->emRegistry),
        ]);
    }

    protected function persist(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }

        $this->em->flush();
        // no clear, because entities managed by the choice field must
        // be managed!
    }

    protected function createRegistryMock($name, $em)
    {
        $registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $registry->method('getManager')
            ->with($this->equalTo($name))
            ->willReturn($em);

        return $registry;
    }
}
