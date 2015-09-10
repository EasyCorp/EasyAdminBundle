<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Reflection;

use JavierEguiluz\Bundle\EasyAdminBundle\Reflection\ClassPropertyReflector;

class ClassPropertyReflectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideGetterValues
     */
    public function testGetGetter($property, $expectedGetter)
    {
        $testClassNamespace = 'JavierEguiluz\Bundle\EasyAdminBundle\Tests\Reflection\fixtures\FooBarClass';

        $reflector = new ClassPropertyReflector();
        $this->assertSame($expectedGetter, $reflector->getGetter($testClassNamespace, $property));
    }

    public function provideGetterValues()
    {
        return array(
            array('publicPropertyWithoutGetterWithoutSetter', null),
            array('protectedPropertyWithoutGetterWithoutSetter', null),
            array('privatePropertyWithoutGetterWithoutSetter', null),
            array('publicPropertyWithSameNameMethod', 'publicPropertyWithSameNameMethod'),
            array('protectedPropertyWithSameNameMethod', 'protectedPropertyWithSameNameMethod'),
            array('privatePropertyWithSameNameMethod', 'privatePropertyWithSameNameMethod'),
            array('publicPropertyWithGetterWithoutSetter', 'getPublicPropertyWithGetterWithoutSetter'),
            array('protectedPropertyWithGetterWithoutSetter', 'getProtectedPropertyWithGetterWithoutSetter'),
            array('privatePropertyWithGetterWithoutSetter', 'getPrivatePropertyWithGetterWithoutSetter'),
            array('publicPropertyWithoutGetterWithSetter', null),
            array('protectedPropertyWithoutGetterWithSetter', null),
            array('privatePropertyWithoutGetterWithSetter', null),
            array('publicPropertyWithGetterWithSetter', 'getPublicPropertyWithGetterWithSetter'),
            array('protectedPropertyWithGetterWithSetter', 'getProtectedPropertyWithGetterWithSetter'),
            array('privatePropertyWithGetterWithSetter', 'getPrivatePropertyWithGetterWithSetter'),
            array('publicPropertyWithHasserWithoutGetterWithoutSetter', 'hasPublicPropertyWithHasserWithoutGetterWithoutSetter'),
            array('protectedPropertyWithHasserWithoutGetterWithoutSetter', 'hasProtectedPropertyWithHasserWithoutGetterWithoutSetter'),
            array('privatePropertyWithHasserWithoutGetterWithoutSetter', 'hasPrivatePropertyWithHasserWithoutGetterWithoutSetter'),
            array('publicPropertyWithIsserWithoutHasserWithoutGetterWithSetter', 'isPublicPropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
            array('protectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter', 'isProtectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
            array('privatePropertyWithIsserWithoutHasserWithoutGetterWithSetter', 'isPrivatePropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
            array('snake_case_public_property_without_getter_without_setter', null),
            array('snake_case_protected_property_without_getter_without_setter', null),
            array('snake_case_private_property_without_getter_without_setter', null),
            array('snake_case_public_property_with_same_name_method', 'snake_case_public_property_with_same_name_method'),
            array('snake_case_protected_property_with_same_name_method', 'snake_case_protected_property_with_same_name_method'),
            array('snake_case_private_property_with_same_name_method', 'snake_case_private_property_with_same_name_method'),
            array('snake_case_public_property_with_getter_without_setter', 'getSnakeCasePublicPropertyWithGetterWithoutSetter'),
            array('snake_case_protected_property_with_getter_without_setter', 'getSnakeCaseProtectedPropertyWithGetterWithoutSetter'),
            array('snake_case_private_property_with_getter_without_setter', 'getSnakeCasePrivatePropertyWithGetterWithoutSetter'),
            array('snake_case_public_property_without_getter_with_setter', null),
            array('snake_case_protected_property_without_getter_with_setter', null),
            array('snake_case_private_property_without_getter_with_setter', null),
            array('snake_case_public_property_with_getter_with_setter', 'getSnakeCasePublicPropertyWithGetterWithSetter'),
            array('snake_case_protected_property_with_getter_with_setter', 'getSnakeCaseProtectedPropertyWithGetterWithSetter'),
            array('snake_case_private_property_with_getter_with_setter', 'getSnakeCasePrivatePropertyWithGetterWithSetter'),
            array('snake_case_public_property_with_hasser_without_getter_with_setter', 'hasSnakeCasePublicPropertyWithHasserWithoutGetterWithSetter'),
            array('snake_case_protected_property_with_hasser_without_getter_with_setter', 'hasSnakeCaseProtectedPropertyWithHasserWithoutGetterWithSetter'),
            array('snake_case_private_property_with_hasser_without_getter_with_setter', 'hasSnakeCasePrivatePropertyWithHasserWithoutGetterWithSetter'),
            array('snake_case_public_property_with_isser_without_hasser_without_getter_with_setter', 'isSnakeCasePublicPropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
            array('snake_case_protected_property_with_isser_without_hasser_without_getter_with_setter', 'isSnakeCaseProtectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
            array('snake_case_private_property_with_isser_without_hasser_without_getter_with_setter', 'isSnakeCasePrivatePropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
        );
    }

    /**
     * @dataProvider provideSetterValues
     */
    public function testGetSetter($property, $expectedSetter)
    {
        $testClassNamespace = 'JavierEguiluz\Bundle\EasyAdminBundle\Tests\Reflection\fixtures\FooBarClass';

        $reflector = new ClassPropertyReflector();
        $this->assertSame($expectedSetter, $reflector->getSetter($testClassNamespace, $property));
    }

    public function provideSetterValues()
    {
        return array(
            array('publicPropertyWithoutGetterWithoutSetter', null),
            array('protectedPropertyWithoutGetterWithoutSetter', null),
            array('privatePropertyWithoutGetterWithoutSetter', null),
            array('publicPropertyWithSameNameMethod', null),
            array('protectedPropertyWithSameNameMethod', null),
            array('privatePropertyWithSameNameMethod', null),
            array('publicPropertyWithGetterWithoutSetter', null),
            array('protectedPropertyWithGetterWithoutSetter', null),
            array('privatePropertyWithGetterWithoutSetter', null),
            array('publicPropertyWithoutGetterWithSetter', 'setPublicPropertyWithoutGetterWithSetter'),
            array('protectedPropertyWithoutGetterWithSetter', 'setProtectedPropertyWithoutGetterWithSetter'),
            array('privatePropertyWithoutGetterWithSetter', 'setPrivatePropertyWithoutGetterWithSetter'),
            array('publicPropertyWithGetterWithSetter', 'setPublicPropertyWithGetterWithSetter'),
            array('protectedPropertyWithGetterWithSetter', 'setProtectedPropertyWithGetterWithSetter'),
            array('privatePropertyWithGetterWithSetter', 'setPrivatePropertyWithGetterWithSetter'),
            array('publicPropertyWithHasserWithoutGetterWithoutSetter', null),
            array('protectedPropertyWithHasserWithoutGetterWithoutSetter', null),
            array('privatePropertyWithHasserWithoutGetterWithoutSetter', null),
            array('publicPropertyWithIsserWithoutHasserWithoutGetterWithSetter', null),
            array('protectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter', null),
            array('privatePropertyWithIsserWithoutHasserWithoutGetterWithSetter', null),
            array('snake_case_public_property_without_getter_without_setter', null),
            array('snake_case_protected_property_without_getter_without_setter', null),
            array('snake_case_private_property_without_getter_without_setter', null),
            array('snake_case_public_property_with_same_name_method', null),
            array('snake_case_protected_property_with_same_name_method', null),
            array('snake_case_private_property_with_same_name_method', null),
            array('snake_case_public_property_with_getter_without_setter', null),
            array('snake_case_protected_property_with_getter_without_setter', null),
            array('snake_case_private_property_with_getter_without_setter', null),
            array('snake_case_public_property_without_getter_with_setter', 'setSnakeCasePublicPropertyWithoutGetterWithSetter'),
            array('snake_case_protected_property_without_getter_with_setter', 'setSnakeCaseProtectedPropertyWithoutGetterWithSetter'),
            array('snake_case_private_property_without_getter_with_setter', 'setSnakeCasePrivatePropertyWithoutGetterWithSetter'),
            array('snake_case_public_property_with_getter_with_setter', 'setSnakeCasePublicPropertyWithGetterWithSetter'),
            array('snake_case_protected_property_with_getter_with_setter', 'setSnakeCaseProtectedPropertyWithGetterWithSetter'),
            array('snake_case_private_property_with_getter_with_setter', 'setSnakeCasePrivatePropertyWithGetterWithSetter'),
            array('snake_case_public_property_with_hasser_without_getter_with_setter', null),
            array('snake_case_protected_property_with_hasser_without_getter_with_setter', null),
            array('snake_case_private_property_with_hasser_without_getter_with_setter', null),
            array('snake_case_public_property_with_isser_without_hasser_without_getter_with_setter', null),
            array('snake_case_protected_property_with_isser_without_hasser_without_getter_with_setter', null),
            array('snake_case_private_property_with_isser_without_hasser_without_getter_with_setter', null),
        );
    }

    /**
     * @dataProvider provideIsPublicValues
     */
    public function testIsPublic($property)
    {
        $testClassNamespace = 'JavierEguiluz\Bundle\EasyAdminBundle\Tests\Reflection\fixtures\FooBarClass';

        $reflector = new ClassPropertyReflector();
        $expectedResult = ('public' === substr($property, 0, 6) || 'snake_case_public' === substr($property, 0, 17)) ? true : false;
        $this->assertSame($expectedResult, $reflector->isPublic($testClassNamespace, $property));
    }

    public function provideIsPublicValues()
    {
        return array(
            array('publicPropertyWithoutGetterWithoutSetter'),
            array('protectedPropertyWithoutGetterWithoutSetter'),
            array('privatePropertyWithoutGetterWithoutSetter'),
            array('publicPropertyWithSameNameMethod'),
            array('protectedPropertyWithSameNameMethod'),
            array('privatePropertyWithSameNameMethod'),
            array('publicPropertyWithGetterWithoutSetter'),
            array('protectedPropertyWithGetterWithoutSetter'),
            array('privatePropertyWithGetterWithoutSetter'),
            array('publicPropertyWithoutGetterWithSetter'),
            array('protectedPropertyWithoutGetterWithSetter'),
            array('privatePropertyWithoutGetterWithSetter'),
            array('publicPropertyWithGetterWithSetter'),
            array('protectedPropertyWithGetterWithSetter'),
            array('privatePropertyWithGetterWithSetter'),
            array('publicPropertyWithHasserWithoutGetterWithoutSetter'),
            array('protectedPropertyWithHasserWithoutGetterWithoutSetter'),
            array('privatePropertyWithHasserWithoutGetterWithoutSetter'),
            array('publicPropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
            array('protectedPropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
            array('privatePropertyWithIsserWithoutHasserWithoutGetterWithSetter'),
            array('snake_case_public_property_without_getter_without_setter'),
            array('snake_case_protected_property_without_getter_without_setter'),
            array('snake_case_private_property_without_getter_without_setter'),
            array('snake_case_public_property_with_same_name_method'),
            array('snake_case_protected_property_with_same_name_method'),
            array('snake_case_private_property_with_same_name_method'),
            array('snake_case_public_property_with_getter_without_setter'),
            array('snake_case_protected_property_with_getter_without_setter'),
            array('snake_case_private_property_with_getter_without_setter'),
            array('snake_case_public_property_without_getter_with_setter'),
            array('snake_case_protected_property_without_getter_with_setter'),
            array('snake_case_private_property_without_getter_with_setter'),
            array('snake_case_public_property_with_getter_with_setter'),
            array('snake_case_protected_property_with_getter_with_setter'),
            array('snake_case_private_property_with_getter_with_setter'),
            array('snake_case_public_property_with_hasser_without_getter_with_setter'),
            array('snake_case_protected_property_with_hasser_without_getter_with_setter'),
            array('snake_case_private_property_with_hasser_without_getter_with_setter'),
            array('snake_case_public_property_with_isser_without_hasser_without_getter_with_setter'),
            array('snake_case_protected_property_with_isser_without_hasser_without_getter_with_setter'),
            array('snake_case_private_property_with_isser_without_hasser_without_getter_with_setter'),
        );
    }
}
