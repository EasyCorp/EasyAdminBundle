<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Util\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Util\FormTypeHelper;
use PHPUnit\Framework\TestCase;

class FormTypeHelperTest extends TestCase
{
    public function formTypeNameAndFqcnProvider()
    {
        return [
            'Symfony Type (regular name)' => ['integer', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\IntegerType'],
            'Symfony Type (irregular name)' => ['datetime', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType'],
            'Doctrine Bridge Type' => ['entity', 'Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType'],
            'Custom Type (short name)' => ['foo', 'foo'],
            'Custom Type (FQCN)' => ['Foo\Bar', 'Foo\Bar'],
        ];
    }

    /**
     * @dataProvider formTypeNameAndFqcnProvider
     */
    public function testGetTypeClass($typeName, $expectedTypeClass)
    {
        $this->assertSame($expectedTypeClass, FormTypeHelper::getTypeClass($typeName));
    }

    /**
     * @dataProvider formTypeNameAndFqcnProvider
     */
    public function testGetTypeName($expectedTypeName, $typeClass)
    {
        $this->assertSame($expectedTypeName, FormTypeHelper::getTypeName($typeClass));
    }
}
