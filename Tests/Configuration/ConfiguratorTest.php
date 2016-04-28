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
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;
use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    private $extension;

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
        $this->markTestSkipped('This test must be updated.');

        $backendConfig = array('easy_admin' => null);
        $configurator = new Configurator(new PropertyAccessor(), '');
        $configurator->getEntityConfig('TestEntity');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Entity "UnmanagedEntity" is not managed by EasyAdmin.
     */
    public function testAccessingAnUnmanagedEntity()
    {
        $this->markTestSkipped('This test must be updated.');

        $backendConfig = array('easy_admin' => array('entities' => array('AppBundle\\Entity\\TestEntity')));
        $configurator = new Configurator(new PropertyAccessor(), '');
        $configurator->getEntityConfig('UnmanagedEntity');
    }
}

class TestEntity
{
    // empty class needed for test fixtures
}
