<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Router;

use EasyCorp\Bundle\EasyAdminBundle\Orm\DqlAliasCreator;
use PHPUnit\Framework\TestCase;

class DqlAliasCreatorTest extends TestCase
{
    /**
     * @dataProvider createAliasDataProvider
     */
    public function testCreateAlias(string $expectedAlias, string $entityName)
    {
        $createdAlias = DqlAliasCreator::create($entityName);

        $this->assertSame($expectedAlias, $createdAlias, sprintf('The created DQL alias for "%s" does not match the expected alias "%s".', $createdAlias, $expectedAlias));
    }

    public static function createAliasDataProvider(): iterable
    {
        yield ['category', 'category'];
        yield ['interval', 'interval']; // "interval" is not a reserved keyword in the Doctrine Query Language but only a reserved keyword in platforms like MySQL
        yield ['ea_order', 'order']; // "order" is a reserved keyword in the Doctrine Query Language and has to be prefixed to avoid runtime exceptions
        yield ['ea_orDER', 'orDER']; // cases are ignored
    }
}
