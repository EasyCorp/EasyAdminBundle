<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\DependencyInjection\Compiler;

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\CommonPhpUnitTestCase;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\NormalizerConfigPass;

class NormalizerConfigPassTest extends CommonPhpUnitTestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The values of the "fields" option for the "edit" view of the "AppBundle\Entity\TestEntity" entity can only be strings or arrays.
     */
    public function testFieldsMustBeStringsOrArrays()
    {
        $backendConfig = array('entities' => array(
            'TestEntity' => array(
                'class' => 'AppBundle\Entity\TestEntity',
                'edit' => array(
                    'fields' => array(20),
                ),
            ),
        ));

        $configPass = new NormalizerConfigPass();
        $configPass->process($backendConfig);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage One of the values of the "fields" option for the "edit" view of the "AppBundle\Entity\TestEntity" entity does not define the "property" option.
     */
    public function testFieldsMustDefinePropertyOption()
    {
        $backendConfig = array('entities' => array(
            'TestEntity' => array(
                'class' => 'AppBundle\Entity\TestEntity',
                'edit' => array(
                    'fields' => array(
                        array('label' => 'Field without "property" option'),
                    ),
                ),
            ),
        ));

        $configPass = new NormalizerConfigPass();
        $configPass->process($backendConfig);
    }
}
