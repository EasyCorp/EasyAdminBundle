<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Util\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Util\LegacyFormHelper;

class LegacyFormHelperTest extends \PHPUnit_Framework_TestCase
{
    public function shortTypesToFqcnProvider()
    {
        return array(
            'Symfony Type (regular name)' => array('integer', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\IntegerType'),
            'Symfony Type (irregular name)' => array('datetime', 'Symfony\\Component\\Form\\Extension\\Core\\Type\\DateTimeType'),
            'Doctrine Bridge Type' => array('entity', 'Symfony\\Bridge\\Doctrine\\Form\\Type\\EntityType'),
            'Custom Type (short name)' => array('foo', 'foo'),
            'Custom Type (FQCN)' => array('Foo\Bar', 'Foo\Bar'),
        );
    }

    /**
     * @dataProvider shortTypesToFqcnProvider
     */
    public function testGetType($shortType, $expected)
    {
        if (LegacyFormHelper::useLegacyFormComponent()) {
            $expected = $shortType;
        }

        $this->assertSame($expected, LegacyFormHelper::getType($shortType));
    }
}
