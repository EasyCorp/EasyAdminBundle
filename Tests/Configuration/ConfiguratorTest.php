<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Configuration;

use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;
use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\CommonPhpUnitTestCase;

class ConfiguratorTest extends CommonPhpUnitTestCase
{
    private $extension;
    private $inspector;

    public function setUp()
    {
        $this->extension = new EasyAdminExtension();
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Entity "TestEntity" is not managed by EasyAdmin.
     */
    public function testEmptyConfiguration()
    {
        $this->markTestSkipped('TODO...');

        $backendConfig = array('easy_admin' => null);
        $configurator = new Configurator($backendConfig, $this->inspector);
        $configurator->getEntityConfiguration('TestEntity');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Entity "UnmanagedEntity" is not managed by EasyAdmin.
     */
    public function testAccessingAnUnmanagedEntity()
    {
        $this->markTestSkipped('TODO...');

        $backendConfig = array('easy_admin' => array('entities' => array('AppBundle\\Entity\\TestEntity')));
        $configurator = new Configurator($backendConfig, $this->inspector);
        $configurator->getEntityConfiguration('UnmanagedEntity');
    }
}

class TestEntity
{
    // empty class needed for test fixtures
}
