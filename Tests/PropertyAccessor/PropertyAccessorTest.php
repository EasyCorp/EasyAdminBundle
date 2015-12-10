<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\PropertyAccessor;

use Symfony\Component\PropertyAccess\PropertyAccessor;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\PropertyAccessor\fixtures\FooBarClass;

class PropertyAccessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideIsWritable
     */
    public function testGetSetter($property, $expectedResult)
    {
        $accessor = new PropertyAccessor();
        $object = new FooBarClass();

        if (!method_exists($accessor, 'isWritable')) {
            $this->markTestSkipped('PropertyAccessor::isWritable method is not available in Symfony 2.3.');
        }

        $this->assertSame($expectedResult, $accessor->isWritable($object, $property));
    }

    public function provideIsWritable()
    {
        return array(
            array('publicPropertyWithoutGetterWithoutSetter', true),
            array('protectedPropertyWithoutGetterWithoutSetter', false),
            array('privatePropertyWithoutGetterWithoutSetter', false),
            array('publicPropertyWithSameNameMethod', true),
            array('protectedPropertyWithSameNameMethod', false),
            array('privatePropertyWithSameNameMethod', false),
            array('publicPropertyWithGetterWithoutSetter', true),
            array('protectedPropertyWithGetterWithoutSetter', false),
            array('privatePropertyWithGetterWithoutSetter', false),
            array('publicPropertyWithoutGetterWithSetter', true),
            array('protectedPropertyWithoutGetterWithSetter', true),
            array('privatePropertyWithoutGetterWithSetter', true),
            array('publicPropertyWithGetterWithSetter', true),
            array('protectedPropertyWithGetterWithSetter', true),
            array('privatePropertyWithGetterWithSetter', true),
            array('publicPropertyWithHasserWithoutGetterWithoutSetter', true),
            array('protectedPropertyWithHasserWithoutGetterWithoutSetter', false),
            array('privatePropertyWithHasserWithoutGetterWithoutSetter', false),
            array('publicPropertyWithIsserWithoutHasserWithoutGetterWithoutSetter', true),
            array('protectedPropertyWithIsserWithoutHasserWithoutGetterWithoutSetter', false),
            array('privatePropertyWithIsserWithoutHasserWithoutGetterWithoutSetter', false),
            array('snake_case_public_property_without_getter_without_setter', true),
            array('snake_case_protected_property_without_getter_without_setter', false),
            array('snake_case_private_property_without_getter_without_setter', false),
            array('snake_case_public_property_with_same_name_method', true),
            array('snake_case_protected_property_with_same_name_method', false),
            array('snake_case_private_property_with_same_name_method', false),
            array('snake_case_public_property_with_getter_without_setter', true),
            array('snake_case_protected_property_with_getter_without_setter', false),
            array('snake_case_private_property_with_getter_without_setter', false),
            array('snake_case_public_property_without_getter_with_setter', true),
            array('snake_case_protected_property_without_getter_with_setter', true),
            array('snake_case_private_property_without_getter_with_setter', true),
            array('snake_case_public_property_with_getter_with_setter', true),
            array('snake_case_protected_property_with_getter_with_setter', true),
            array('snake_case_private_property_with_getter_with_setter', true),
            array('snake_case_public_property_with_hasser_without_getter_without_setter', true),
            array('snake_case_protected_property_with_hasser_without_getter_without_setter', false),
            array('snake_case_private_property_with_hasser_without_getter_without_setter', false),
            array('snake_case_public_property_with_isser_without_hasser_without_getter_without_setter', true),
            array('snake_case_protected_property_with_isser_without_hasser_without_getter_without_setter', false),
            array('snake_case_private_property_with_isser_without_hasser_without_getter_without_setter', false),
        );
    }

    /**
     * @dataProvider provideIsReadable
     */
    public function testIsReadable($property, $expectedResult)
    {
        $accessor = new PropertyAccessor();
        $object = new FooBarClass();

    if (!method_exists($accessor, 'isReadable')) {
            $this->markTestSkipped('PropertyAccessor::isReadable method is not available in Symfony 2.3.');
        }

        $this->assertSame($expectedResult, $accessor->isReadable($object, $property));
    }

    public function provideIsReadable()
    {
        return array(
            array('publicPropertyWithoutGetterWithoutSetter', true),
            array('protectedPropertyWithoutGetterWithoutSetter', false),
            array('privatePropertyWithoutGetterWithoutSetter', false),
            array('publicPropertyWithSameNameMethod', true),
            array('protectedPropertyWithSameNameMethod', true),
            array('privatePropertyWithSameNameMethod', true),
            array('publicPropertyWithGetterWithoutSetter', true),
            array('protectedPropertyWithGetterWithoutSetter', true),
            array('privatePropertyWithGetterWithoutSetter', true),
            array('publicPropertyWithoutGetterWithSetter', true),
            array('protectedPropertyWithoutGetterWithSetter', false),
            array('privatePropertyWithoutGetterWithSetter', false),
            array('publicPropertyWithGetterWithSetter', true),
            array('protectedPropertyWithGetterWithSetter', true),
            array('privatePropertyWithGetterWithSetter', true),
            array('publicPropertyWithHasserWithoutGetterWithoutSetter', true),
            array('protectedPropertyWithHasserWithoutGetterWithoutSetter', true),
            array('privatePropertyWithHasserWithoutGetterWithoutSetter', true),
            array('publicPropertyWithIsserWithoutHasserWithoutGetterWithoutSetter', true),
            array('protectedPropertyWithIsserWithoutHasserWithoutGetterWithoutSetter', true),
            array('privatePropertyWithIsserWithoutHasserWithoutGetterWithoutSetter', true),
            array('snake_case_public_property_without_getter_without_setter', true),
            array('snake_case_protected_property_without_getter_without_setter', false),
            array('snake_case_private_property_without_getter_without_setter', false),
            array('snake_case_public_property_with_same_name_method', true),
            array('snake_case_protected_property_with_same_name_method', false),
            array('snake_case_private_property_with_same_name_method', false),
            array('snake_case_public_property_with_getter_without_setter', true),
            array('snake_case_protected_property_with_getter_without_setter', true),
            array('snake_case_private_property_with_getter_without_setter', true),
            array('snake_case_public_property_without_getter_with_setter', true),
            array('snake_case_protected_property_without_getter_with_setter', false),
            array('snake_case_private_property_without_getter_with_setter', false),
            array('snake_case_public_property_with_getter_with_setter', true),
            array('snake_case_protected_property_with_getter_with_setter', true),
            array('snake_case_private_property_with_getter_with_setter', true),
            array('snake_case_public_property_with_hasser_without_getter_without_setter', true),
            array('snake_case_protected_property_with_hasser_without_getter_without_setter', true),
            array('snake_case_private_property_with_hasser_without_getter_without_setter', true),
            array('snake_case_public_property_with_isser_without_hasser_without_getter_without_setter', true),
            array('snake_case_protected_property_with_isser_without_hasser_without_getter_without_setter', true),
            array('snake_case_private_property_with_isser_without_hasser_without_getter_without_setter', true),
        );
    }
}
