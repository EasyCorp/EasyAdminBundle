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

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;
use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\CommonPhpUnitTestCase;
use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\Compiler\EasyAdminConfigurationPass;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class EasyAdminConfigurationPassTest extends AbstractTestCase
{
    /**
     * @dataProvider provideConfigurationFiles
     */
    public function testBackendConfigurations($inputFixtureFilepath, $outputFixtureFilepath)
    {
        $isSymfony23 = 2 == Kernel::MAJOR_VERSION && 3 == Kernel::MINOR_VERSION;
        if ($isSymfony23 && !$this->isTestCompatibleWithSymfony23($inputFixtureFilepath)) {
            $this->markTestSkipped('This test is not compatible with Symfony 2.3 because the YAML component of that version does not ignore duplicate keys.');
        }

        $configuration = Yaml::parse(file_get_contents($inputFixtureFilepath));
        $app = new \ConfigPassKernel($configuration);
        $app->boot();

        $this->assertConfigurationParameterMatchesExpectedValue($app->getContainer(), $outputFixtureFilepath);
    }

    private function assertConfigurationParameterMatchesExpectedValue($container, $expectedConfigFile)
    {
        $expectedConfiguration = Yaml::parse(file_get_contents($expectedConfigFile));
        $actualConfiguration = $container->getParameter('easyadmin.config');

        $this->assertEquals($expectedConfiguration['easy_admin'], $actualConfiguration);
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

    private function isTestCompatibleWithSymfony23($filepath)
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
