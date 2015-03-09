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
        $backendConfig = Yaml::parse($inputFixtureFilepath);
        $configuration = $this->extension->getEntitiesConfiguration($backendConfig['easy_admin']['entities']);

        $expectedResult = include $outputFixtureFilepath;

        $this->assertEquals($expectedResult, $configuration, sprintf("%s configuration is correctly parsed into %s", basename($inputFixtureFilepath), basename($outputFixtureFilepath)));
    }

    public function provideConfigurationFixtures()
    {
        $fixtures = array();

        $inputs = glob(__DIR__.'/fixtures/input/admin_*.yml');
        $outputs = glob(__DIR__.'/fixtures/output/config_*.php');

        for ($i = 0; $i < count($inputs); $i++) {
            $fixtures[] = array($inputs[$i], $outputs[$i]);
        }

        return $fixtures;
    }
}
