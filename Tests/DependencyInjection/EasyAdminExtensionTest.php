<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;
use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension;

class EasyAdminExtensionTest extends \PHPUnit_Framework_TestCase
{
    private $container;
    private $loader;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->loader = new EasyAdminExtension();
    }

    /**
     * @dataProvider provideConfigurationFiles
     */
    public function testBackendConfigurations($inputFixtureFilepath, $outputFixtureFilepath)
    {
        if ('2.3.x' === getenv('SYMFONY_VERSION') && !$this->isTestCompatibleWithYamlComponent($inputFixtureFilepath)) {
            $this->markTestSkipped('This test fails because of the behavior of the YAML component in Symfony 2.3.x version.');
        }

        $parsedConfiguration = $this->parseConfigurationFile($inputFixtureFilepath);
        $expectedConfiguration = file_get_contents($outputFixtureFilepath);

        $this->assertEquals($expectedConfiguration, $parsedConfiguration, sprintf('%s configuration is correctly parsed into %s', $inputFixtureFilepath, $outputFixtureFilepath));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The "TestEntity" entity must define its associated Doctrine entity class using the "class" option.
     */
    public function testClassOptionIsMandatoryFoEntityConfiguration()
    {
        $this->parseConfigurationFile(__DIR__.'/fixtures/exceptions/class_option_is_mandatory_for_expanded_entity_configuration.yml');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage One of the values of the "fields" option for the "new" view of the "AppBundle\Entity\TestEntity" entity does not define the "property" option.
     */
    public function testPropertyOptionIsMandatoryForFields()
    {
        $this->parseConfigurationFile(__DIR__.'/fixtures/exceptions/property_option_is_mandatory_for_fields.yml');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The values of the "fields" option for the "new" view of the "AppBundle\Entity\TestEntity" entity can only be strings or arrays.
     */
    public function testFieldsCanOnlyBeStringsOrArrays()
    {
        $this->parseConfigurationFile(__DIR__.'/fixtures/exceptions/fields_can_only_be_strings_or_arrays.yml');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage When using the expanded configuration format for actions, you must define their "name" option.
     */
    public function testNameOptionIsMandatoryForActionConfiguration()
    {
        $this->parseConfigurationFile(__DIR__.'/fixtures/exceptions/name_option_is_mandatory_for_action_configuration.yml');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The name of the "invalid-Action~Name!!" action contains invalid characters (allowed: letters, numbers, underscores).
     */
    public function testActionNameCannotContainInvalidCharacters()
    {
        $this->parseConfigurationFile(__DIR__.'/fixtures/exceptions/action_name_cannot_contain_invalid_characters.yml');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The name of the "Invalid action name" action contains invalid characters (allowed: letters, numbers, underscores).
     */
    public function testActionNameCannotContainWhiteSpaces()
    {
        $this->parseConfigurationFile(__DIR__.'/fixtures/exceptions/action_name_cannot_contain_white_spaces.yml');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The name of the "7invalidActionName" action contains invalid characters (allowed: letters, numbers, underscores).
     */
    public function testActionNameCannotStartWithANumber()
    {
        $this->parseConfigurationFile(__DIR__.'/fixtures/exceptions/action_name_cannot_start_with_a_number.yml');
    }

    public function provideConfigurationFiles()
    {
        $fixtures = array();

        $inputs = glob(__DIR__.'/fixtures/*/input/admin_*.yml');
        $outputs = glob(__DIR__.'/fixtures/*/output/config_*.yml');

        for ($i = 0; $i < count($inputs); $i++) {
            $fixtures[] = array($inputs[$i], $outputs[$i]);
        }

        return $fixtures;

        return $this->lookForFixturesFiles();
    }

    /**
     * Given the filepath of the original backend YAML configuration, it returns
     * the configuration parsed by the container and dumped into YAML format.
     *
     * @param string $filepath
     *
     * @return string
     */
    private function parseConfigurationFile($filepath)
    {
        $inputConfiguration = Yaml::parse(file_get_contents($filepath));
        $this->loader->load($inputConfiguration, $this->container);

        $easyAdminConfigParameter = $this->container->getParameter('easyadmin.config');
        $parsedConfiguration = Yaml::dump(array('easy_admin' => $easyAdminConfigParameter), INF);

        return $parsedConfiguration;
    }

    private function isTestCompatibleWithYamlComponent($filepath)
    {
        $incompatibleTests = array(
            'configurations/input/admin_007.yml',
            'configurations/input/admin_008.yml',
            'configurations/input/admin_013.yml',
            'configurations/input/admin_014.yml',
            'configurations/input/admin_015.yml',
            'configurations/input/admin_020.yml',
            'configurations/input/admin_021.yml',
            'configurations/input/admin_026.yml',
        );

        return !in_array(substr($filepath, -34), $incompatibleTests);
    }
}
