<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Form\Type;

use JavierEguiluz\Bundle\EasyAdminBundle\Form\Type\EasyAdminFormType;

class EasyAdminFormTypeTest extends \PHPUnit_Framework_TestCase
{
    public function shortTypesToFqcnProvider()
    {
        return array(
            'Symfony native form type'         => array('integer', 'Symfony\Component\Form\Extension\Core\Type\IntegerType'),
            'Symfony DateTime form type'       => array('datetime', 'Symfony\Component\Form\Extension\Core\Type\DateTimeType'),
            'Doctrine Bridge Entity form type' => array('entity', 'Symfony\Bridge\Doctrine\Form\Type\EntityType'),
            'Custom form type'                 => array('foo', 'foo'),
            'FQCN'                             => array('Foo\Bar', 'Foo\Bar'),
        );
    }

    /**
     * @dataProvider shortTypesToFqcnProvider
     */
    public function testGetFormTypeFqcn($shortType, $expected)
    {
        $type = new EasyAdminFormType(
            $this->getMockBuilder('JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator')->disableOriginalConstructor()->getMock(),
            array(),
            $this->getMockBuilder('Symfony\Component\Form\FormTypeGuesserInterface')->disableOriginalConstructor()->getMock()
        );

        $method = new \ReflectionMethod($type, 'getFormTypeFqcn');
        $method->setAccessible(true);
        $this->assertSame($expected, $method->invoke($type, $shortType));
        $method->setAccessible(false);
    }
}
