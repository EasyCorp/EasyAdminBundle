<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Form\Type;

use JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\EasyAdminFormType;

class EasyAdminFormTypeTest extends \PHPUnit_Framework_TestCase
{
    public function shortTypesToFqcnProvider()
    {
        return array(
            'Symfony Type (regular name)'   => array('integer', 'Symfony\Component\Form\Extension\Core\Type\IntegerType'),
            'Symfony Type (irregular name)' => array('datetime', 'Symfony\Component\Form\Extension\Core\Type\DateTimeType'),
            'Doctrine Bridge Type'          => array('entity', 'Symfony\Bridge\Doctrine\Form\Type\EntityType'),
            'Custom Type (short name)'      => array('foo', 'foo'),
            'Custom Type (FQCN)'            => array('Foo\Bar', 'Foo\Bar'),
        );
    }

    /**
     * @dataProvider shortTypesToFqcnProvider
     */
    public function testGetFormTypeFqcn($shortType, $expected)
    {
        $configuratorMock = $this->getMockBuilder('JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator')->disableOriginalConstructor()->getMock();
        $type = new EasyAdminFormType($configuratorMock, array(), array());

        $method = new \ReflectionMethod($type, 'getFormTypeFqcn');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($type, $shortType));

        $method->setAccessible(false);
    }
}
