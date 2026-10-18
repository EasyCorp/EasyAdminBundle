<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class EntityFilterTypeTest extends TypeTestCase
{
    private ?EntityManager $entityManager;
    private ?ManagerRegistry $managerRegistry;

    protected function setUp(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Extension pdo_sqlite is required.');
        }

        $config = new Configuration();
        $config->setEntityNamespaces(['EasyAdminTestsDoctrine' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type']);
        if (\PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        } else {
            $config->setAutoGenerateProxyClasses(true);
            $config->setProxyDir(sys_get_temp_dir());
            $config->setProxyNamespace('EasyAdminBundle\Doctrine');
            // ORM 3 always uses lazy ghost objects; ORM 2 deprecates not enabling them
            if (!$config->isLazyGhostObjectEnabled()) {
                $config->setLazyGhostObjectEnabled(true);
            }
        }
        // ORM 2 deprecates not configuring a metadata cache
        $config->setMetadataCache(new ArrayAdapter());
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ => 'EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type'], true));

        // DBAL 4 always uses this factory; DBAL 3 deprecates not configuring it
        $config->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        $eventManager = new EventManager();
        $this->entityManager = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config, $eventManager),
            $config,
            $eventManager,
        );

        $this->managerRegistry = $this->createStub(ManagerRegistry::class);
        $this->managerRegistry->method('getManager')
            ->willReturn($this->entityManager);

        parent::setUp();

        $schemaTool = new SchemaTool($this->entityManager);
        $classes = [
            $this->entityManager->getClassMetadata(SingleIntIdEntity::class),
        ];

        try {
            $schemaTool->dropSchema($classes);
        } catch (\Exception) {
        }

        try {
            $schemaTool->createSchema($classes);
        } catch (\Exception) {
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager = null;
        $this->managerRegistry = null;
    }

    protected function getExtensions(?ViolationMapperInterface $violationMapper = null): array
    {
        return array_merge(parent::getExtensions(), [
            new DoctrineOrmExtension($this->managerRegistry),
        ]);
    }

    #[DataProvider('toOne')]
    public function testToOne(array $options, array $dataToSubmit, array $expectedData): void
    {
        $this->entityManager->persist(new SingleIntIdEntity(1, 'Foo'));
        $this->entityManager->persist(new SingleIntIdEntity(2, 'Bar'));
        $this->entityManager->flush();

        $form = $this->factory->create(EntityFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertEquals($expectedData, $form->getData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(EntityFilterType::class, $form->getConfig()->getType()->getInnerType());
    }

    public static function toOne(): iterable
    {
        yield [
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => false,
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => 2],
            ['comparison' => '=', 'value' => new SingleIntIdEntity(2, 'Bar')],
        ];
        yield [
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => false,
                ],
            ],
            ['comparison' => ComparisonType::NEQ, 'value' => 1],
            ['comparison' => '!=', 'value' => new SingleIntIdEntity(1, 'Foo')],
        ];
        yield [
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => false,
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
        ];
        yield [
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => true,
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => new ArrayCollection()],
        ];
    }

    #[DataProvider('toMany')]
    public function testToMany(array $options, array $dataToSubmit, array $data): void
    {
        $this->entityManager->persist(new SingleIntIdEntity(1, 'Foo'));
        $this->entityManager->persist(new SingleIntIdEntity(2, 'Bar'));
        $this->entityManager->flush();

        $form = $this->factory->create(EntityFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertEquals($data, $form->getData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(EntityFilterType::class, $form->getConfig()->getType()->getInnerType());
    }

    public static function toMany(): iterable
    {
        yield [
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => true,
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => new ArrayCollection()],
        ];
        yield [
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => true,
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => [1, 2]],
            ['comparison' => 'IN', 'value' => new ArrayCollection([new SingleIntIdEntity(1, 'Foo'), new SingleIntIdEntity(2, 'Bar')])],
        ];
        yield [
            [
                'value_type_options' => [
                    'em' => 'default',
                    'class' => SingleIntIdEntity::class,
                    'multiple' => true,
                ],
            ],
            ['comparison' => ComparisonType::NEQ, 'value' => [1, 2]],
            ['comparison' => 'NOT IN', 'value' => new ArrayCollection([new SingleIntIdEntity(1, 'Foo'), new SingleIntIdEntity(2, 'Bar')])],
        ];
    }
}

#[Entity]
class SingleIntIdEntity
{
    public function __construct(
        #[Id, Column(type: 'integer')]
        protected int $id,

        #[Column(type: 'string', nullable: true)]
        public ?string $name,
    ) {
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
