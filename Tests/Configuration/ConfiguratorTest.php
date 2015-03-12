<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Configuration;

use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use JavierEguiluz\Bundle\EasyAdminBundle\Reflection\EntityMetadataInspector;
use JavierEguiluz\Bundle\EasyAdminBundle\Reflection\ClassPropertyReflector;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;
use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension;

class ConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    private $extension;
    private $inspector;
    private $reflector;

    public function setUp()
    {
        $this->extension = new EasyAdminExtension();

        $entityMetadataStub = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')->disableOriginalConstructor()->getMock();
        $entityMetadataStub->method('getSingleIdentifierFieldName')->willReturn(array('id'));

        $inspectorStub = $this->getMockBuilder('JavierEguiluz\Bundle\EasyAdminBundle\Reflection\EntityMetadataInspector')->disableOriginalConstructor()->getMock();
        $inspectorStub->method('getEntityMetadata')->willReturn($entityMetadataStub);
        $this->inspector = $inspectorStub;

        $reflectorStub = $this->getMockBuilder('JavierEguiluz\Bundle\EasyAdminBundle\Reflection\ClassPropertyReflector')->disableOriginalConstructor()->getMock();
        $this->reflector = $reflectorStub;
    }

    /**
     * @dataProvider provideConfigurationFixtures
     */
    public function testGetEntityConfiguration($inputFixtureFilepath, $outputFixtureFilepath)
    {
        $this->markTestSkipped('Skip test until we can find the solution for the following error: "Argument 1 passed to EasyAdminBundle\Configuration\Configurator::processEntityPropertiesMetadata() must be an instance of Doctrine\ORM\Mapping\ClassMetadata, instance of Mock_ClassMetadataInfo_2057af3e given');

        $backendConfig = Yaml::parse(file_get_contents($inputFixtureFilepath));
        $backendConfig['easy_admin']['entities'] = $this->extension->getEntitiesConfiguration($backendConfig['easy_admin']['entities']);
        $configurator = new Configurator($backendConfig['easy_admin'], $this->inspector, $this->reflector);
        $configuration = $configurator->getEntityConfiguration('TestEntity');
        // Yaml component dumps empty arrays as hashes, fix it to increase configuration readability
        $yamlConfiguration = str_replace('{  }', '[]', Yaml::dump($configuration));

        $expectedConfiguration = file_get_contents($outputFixtureFilepath);

        $this->assertEquals($expectedConfiguration, $yamlConfiguration, sprintf("%s configuration is correctly parsed into %s", basename($inputFixtureFilepath), basename($outputFixtureFilepath)));
    }

    public function provideConfigurationFixtures()
    {
        $fixtures = array();

        $inputs = glob(__DIR__.'/fixtures/input/admin_*.yml');
        $outputs = glob(__DIR__.'/fixtures/output/config_*.yml');

        for ($i = 0; $i < count($inputs); $i++) {
            $fixtures[] = array($inputs[$i], $outputs[$i]);
        }

        return $fixtures;
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Entity "TestEntity" is not managed by EasyAdmin.
     */
    public function testAccessingAnUnmanagedEntity()
    {
        $backendConfig = array('easy_admin' => array('entities' => array()));
        $configurator = new Configurator($backendConfig, $this->inspector, $this->reflector);
        $configuration = $configurator->getEntityConfiguration('TestEntity');
    }
}
