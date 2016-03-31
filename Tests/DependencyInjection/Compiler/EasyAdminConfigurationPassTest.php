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

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

class EasyAdminConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideConfigurationFiles
     */
    public function testBackendConfigurations($inputFixtureFilepath, $outputFixtureFilepath)
    {
        if (!$this->isTestCompatible($inputFixtureFilepath)) {
            $this->markTestSkipped('This test is not compatible with this Symfony Version.');
        }

        $configuration = Yaml::parse(file_get_contents($inputFixtureFilepath));
        $app = new \ConfigPassKernel($configuration['easy_admin']);
        $app->boot();

        $this->assertBackendConfigIsCorrect($app->getContainer(), $outputFixtureFilepath);
    }

    private function assertBackendConfigIsCorrect($container, $expectedConfigFile)
    {
        $expectedConfiguration = Yaml::parse(file_get_contents($expectedConfigFile));
        $actualConfiguration = $container->getParameter('easyadmin.config');

        // 'assertEquals()' is not used because storing the full processed backend
        // configuration would make fixtures too big
        $this->assertArraySubset($expectedConfiguration['easy_admin'], $actualConfiguration);
    }

    public function provideConfigurationFiles()
    {
        $inputs = array_merge(
            glob(__DIR__.'/fixtures/configurations/input/admin_*.yml'),
            glob(__DIR__.'/fixtures/deprecations/input/admin_*.yml'),
            glob(__DIR__.'/fixtures/templates/*/input/admin_*.yml')
        );
        $outputs = array_merge(
            glob(__DIR__.'/fixtures/configurations/output/config_*.yml'),
            glob(__DIR__.'/fixtures/deprecations/output/config_*.yml'),
            glob(__DIR__.'/fixtures/templates/*/output/config_*.yml')
        );

        return array_map(null, $inputs, $outputs);
    }

    private function isTestCompatible($filepath)
    {
        if (2 != Kernel::MAJOR_VERSION || 3 != Kernel::MINOR_VERSION) {
            return true;
        }

        // these tests are not compatible with Symfony 2.3 because the YAML
        // component of that version does not ignore duplicate keys
        $incompatibleTestsWithSymfony23 = array(
            'configurations/input/admin_007.yml',
            'configurations/input/admin_008.yml',
            'configurations/input/admin_013.yml',
            'configurations/input/admin_014.yml',
            'configurations/input/admin_015.yml',
            'configurations/input/admin_020.yml',
            'configurations/input/admin_021.yml',
            'configurations/input/admin_026.yml',
        );

        return !in_array(substr($filepath, -34), $incompatibleTestsWithSymfony23);
    }
}
