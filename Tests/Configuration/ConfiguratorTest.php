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
    private $reflector;

    public function setUp()
    {
        $this->extension = new EasyAdminExtension();

        $entityMetadataStub = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();
        $entityMetadataStub->method('getSingleIdentifierFieldName')->willReturn(array('id'));

        $inspectorStub = $this->getMockBuilder('JavierEguiluz\Bundle\EasyAdminBundle\Reflection\EntityMetadataInspector')->disableOriginalConstructor()->getMock();
        $inspectorStub->method('getEntityMetadata')->willReturn($entityMetadataStub);
        $this->inspector = $inspectorStub;

        $reflectorStub = $this->getMockBuilder('JavierEguiluz\Bundle\EasyAdminBundle\Reflection\ClassPropertyReflector')->disableOriginalConstructor()->getMock();
        $this->reflector = $reflectorStub;
    }

    /**
     * @dataProvider provideConfigurationFiles
     */
    public function testGetEntityConfiguration($inputFixtureFilepath, $outputFixtureFilepath)
    {
        $backendConfig = Yaml::parse(file_get_contents($inputFixtureFilepath));
        $backendConfig['easy_admin']['entities'] = $this->extension->getEntitiesConfiguration($backendConfig['easy_admin']['entities']);
        $configurator = new Configurator($backendConfig['easy_admin'], $this->inspector, $this->reflector);
        $configuration = $configurator->getEntityConfiguration('TestEntity');
        // Yaml component dumps empty arrays as hashes, fix it to increase configuration readability
        $yamlConfiguration = str_replace('{  }', '[]', Yaml::dump($configuration));

        $expectedConfiguration = file_get_contents($outputFixtureFilepath);
        $expectedConfiguration = str_replace("\r", '', $expectedConfiguration);// Prevents bugs from different git crlf config

        $this->assertEquals($expectedConfiguration, $yamlConfiguration, sprintf('%s configuration is not correctly parsed into %s', basename($inputFixtureFilepath), basename($outputFixtureFilepath)));
    }

    public function provideConfigurationFiles($fixturesDir)
    {
        return parent::provideConfigurationFiles(__DIR__.'/fixtures');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Entity "TestEntity" is not managed by EasyAdmin.
     */
    public function testEmptyConfiguration()
    {
        $backendConfig = array('easy_admin' => null);
        $configurator = new Configurator($backendConfig, $this->inspector, $this->reflector);
        $configurator->getEntityConfiguration('TestEntity');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Entity "UnmanagedEntity" is not managed by EasyAdmin.
     */
    public function testAccessingAnUnmanagedEntity()
    {
        $backendConfig = array('easy_admin' => array('entities' => array('AppBundle\\Entity\\TestEntity')));
        $configurator = new Configurator($backendConfig, $this->inspector, $this->reflector);
        $configurator->getEntityConfiguration('UnmanagedEntity');
    }
}
