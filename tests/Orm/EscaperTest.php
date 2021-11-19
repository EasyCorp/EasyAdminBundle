<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Router;

use EasyCorp\Bundle\EasyAdminBundle\Orm\Escaper;
use PHPUnit\Framework\TestCase;

class EscaperTest extends TestCase
{
    /**
     * @dataProvider sqlAliasDataProvider
     */
    public function testEscapeDqlAlias(string $expectedAlias, string $entityName)
    {
        $createdAlias = Escaper::escapeDqlAlias($entityName);

        $this->assertSame($expectedAlias, $createdAlias, sprintf('The created DQL alias for "%s" does not match the expected alias "%s".', $createdAlias, $expectedAlias));
    }

    public static function sqlAliasDataProvider(): iterable
    {
        yield ['category', 'category'];
        // "interval" is not a reserved keyword in the Doctrine Query Language but only a reserved keyword in platforms like MySQL
        yield ['interval', 'interval'];
        // "order" is a reserved keyword in the Doctrine Query Language and has to be prefixed to avoid runtime exceptions
        yield ['ea_order', 'order'];
        // cases are ignored
        yield ['ea_orDER', 'orDER'];
    }
}
