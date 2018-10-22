<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\DTO;

use AppTestBundle\Entity\UnitTests\Product;
use AppTestBundle\Form\DTO\EditProductDTO;
use AppTestBundle\Form\DTO\NewProductDTO;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Form\DTO\DTOFactoryStorage;
use EasyCorp\Bundle\EasyAdminBundle\Form\DTO\DTOFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DTOFactoryTest extends TestCase
{
    /**
     * @dataProvider provideTestDataToCreateDTO
     */
    public function testDTOCreationWithProvidedData(string $action, string $dtoClassToCheck, array $configParams = [], $defaultData = null)
    {
        $container = $this->createMock(ContainerInterface::class);

        $factory = new DTOFactoryStorage($this->getConfigManager($configParams), $container);

        $dto = $factory->createEntityDTO('Product', $action, $defaultData);

        static::assertInstanceOf($dtoClassToCheck, $dto);
    }

    public function provideTestDataToCreateDTO()
    {
        yield 'create_new_DTO_from_default_factory' => [
            'new',
            NewProductDTO::class,
        ];

        yield 'create_edit_DTO_from_default_factory' => [
            'edit',
            EditProductDTO::class,
            [],
            new Product(),
        ];

        yield 'create_new_DTO_from_constructor' => [
            'new',
            NewProductDTO::class,
            ['new' => ['dto_factory' => '__construct']],
        ];

        yield 'create_edit_DTO_from_constructor' => [
            'edit',
            EditProductDTO::class,
            ['edit' => ['dto_factory' => '__construct']],
            new Product(),
        ];

        yield 'create_new_DTO_from_static_factory' => [
            'new',
            NewProductDTO::class,
            ['new' => ['dto_factory' => StaticDTOFactoryTest::class.'::createNewDTO']],
        ];

        yield 'create_edit_DTO_from_static_factory' => [
            'edit',
            EditProductDTO::class,
            ['edit' => ['dto_factory' => StaticDTOFactoryTest::class.'::createEditDTO']],
            new Product(),
        ];
    }

    public function testNewDTOCreationFromContainer()
    {
        $serviceFactory = $this->createMock(DTOFactoryInterface::class);
        $serviceFactory
            ->expects($this->once())
            ->method('createDTO')
            ->with(EditProductDTO::class, 'new', null)
            ->willReturn(new NewProductDTO())
        ;

        $configParams = ['new' => ['dto_class' => EditProductDTO::class, 'dto_factory' => 'test_factory']];

        $factory = new DTOFactoryStorage($this->getConfigManager($configParams));
        $factory->addFactory('test_factory', $serviceFactory);

        $dto = $factory->createEntityDTO('Product', 'new');

        static::assertInstanceOf(NewProductDTO::class, $dto);
    }

    public function testEditDTOCreationFromContainer()
    {
        $defaultData = new Product();

        $serviceFactory = $this->createMock(DTOFactoryInterface::class);
        $serviceFactory
            ->expects($this->once())
            ->method('createDTO')
            ->with(EditProductDTO::class, 'edit', $defaultData)
            ->willReturn(new EditProductDTO($defaultData))
        ;

        $configParams = ['edit' => ['dto_class' => EditProductDTO::class, 'dto_factory' => 'test_factory']];

        $factory = new DTOFactoryStorage($this->getConfigManager($configParams));
        $factory->addFactory('test_factory', $serviceFactory);

        $dto = $factory->createEntityDTO('Product', 'edit', $defaultData);

        static::assertInstanceOf(EditProductDTO::class, $dto);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Could not find a way to create a DTO for entity Product with configured factory inexistent_class::inexistent_method.
     */
    public function testNoFactoryAvailableToCreateDTO()
    {
        $container = $this->createMock(ContainerInterface::class);

        $factory = new DTOFactoryStorage($this->getConfigManager([
            'new' => [
                'dto_factory' => 'inexistent_class::inexistent_method',
            ],
        ]), $container);

        $factory->createEntityDTO('Product', 'new');
    }

    private function getConfigManager(array $entityConfig = []): ConfigManager
    {
        $processedConfig = [
            'entities' => [
                'Product' => array_replace_recursive(
                    [
                        'class' => Product::class,
                        'new' => [
                            'fields' => [],
                            'dto_class' => NewProductDTO::class,
                            'dto_factory' => null,
                            'dto_entity_callable' => null,
                        ],
                        'edit' => [
                            'dto_class' => EditProductDTO::class,
                            'dto_factory' => null,
                            'dto_entity_callable' => null,
                        ],
                    ], $entityConfig
                ),
            ],
        ];

        $cache = new ArrayAdapter();

        // the name must be like the private const ConfigManager::CACHE_KEY
        $cacheItem = $cache->getItem('easyadmin.processed_config');
        $cacheItem->set($processedConfig);
        $cache->save($cacheItem);

        return new ConfigManager([], false, new PropertyAccessor(), $cache);
    }
}

class StaticDTOFactoryTest
{
    public static function createNewDTO()
    {
        return new NewProductDTO();
    }

    public static function createEditDTO(Product $product)
    {
        return new EditProductDTO($product);
    }
}
