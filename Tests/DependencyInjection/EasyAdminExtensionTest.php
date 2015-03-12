<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\DependencyInjection;

use Symfony\Component\Yaml\Yaml;
use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension;

class EasyAdminExtensionTest extends \PHPUnit_Framework_TestCase
{
    private $extension;

    public function setUp()
    {
        $this->extension = new EasyAdminExtension();
    }

    /**
     * @dataProvider provideConfigurationFixtures
     */
    public function testGetEntitiesConfiguration($inputFixtureFilepath, $outputFixtureFilepath)
    {
        $backendConfig = Yaml::parse(file_get_contents($inputFixtureFilepath));
        $configuration = $this->extension->getEntitiesConfiguration($backendConfig['easy_admin']['entities']);
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
     * @expectedException RuntimeException
     * @expectedExceptionMessage One of the values of the "fields" option for the "new" action of the "AppBundle\Entity\TestEntity" entity does not define the "property" option.
     */
    public function testPropertyOptionIsMandatoryForFields()
    {
        $configuration = $this->extension->getEntitiesConfiguration(array(
            'TestEntity' => array(
                'class' => 'AppBundle\\Entity\\TestEntity',
                'new' => array('fields' => array(
                    array('label' => 'field')
                ))
            )
        ));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The values of the "fields" option for the "new" action of the "AppBundle\Entity\TestEntity" entity can only be strings or arrays.
     */
    public function testFieldsCanOnlyBeStringsOrArrays()
    {
        $configuration = $this->extension->getEntitiesConfiguration(array(
            'TestEntity' => array(
                'class' => 'AppBundle\\Entity\\TestEntity',
                'new' => array('fields' => array(
                    20
                ))
            )
        ));
    }
}
