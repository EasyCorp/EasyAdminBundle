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

class EasyAdminConfigurationPassExceptionsTest extends AbstractTestCase
{

    /**
     * @dataProvider provideConfigurationFiles
     */
    public function testBackendConfigurations($configFilePath)
    {
//$configFilePath = '/Users/javier/sf/EasyAdminBundle/Tests/DependencyInjection/Compiler/fixtures/exceptions/name_option_is_mandatory_for_action_configuration.yml';
        $config = Yaml::parse(file_get_contents($configFilePath));

        if (isset($config['expected_exception']['class'])) {
            if (isset($config['expected_exception']['message_string'])) {
                $this->setExpectedException($config['expected_exception']['class'], $config['expected_exception']['message_string']);
            } elseif (isset($config['expected_exception']['message_regexp'])) {
                $this->setExpectedExceptionRegExp($config['expected_exception']['class'], $config['expected_exception']['message_regexp']);
            }
        }

        $app = new \ConfigPassKernel($config);
        $app->boot();
    }

    public function provideConfigurationFiles()
    {
        // glob() returns an array of strings and fixtures require an array of arrays
        return array_map(
            function ($v) { return array($v); },
            glob(__DIR__.'/fixtures/exceptions/*.yml')
        );
    }

    // /**
    //  * Tests the template overriding mechanism when a given entity defines
    //  * its own custom templates in app/Resources/views/easy_admin/<entityName>/<templateName>.html.twig files
    //  * See EasyAdminExtension::processEntityTemplates().
    //  */
    // public function testEntityOverridesDefaultTemplates()
    // {
    //     $fixturesDir = __DIR__.'/fixtures/templates/overridden_by_entity';

    //     foreach (range(1, 5) as $i) {
    //         $this->parseConfigurationFile($fixturesDir.'/input/admin_00'.$i.'.yml', $fixturesDir);

    //         $this->assertConfigurationParameterMatchesExpectedValue($fixturesDir.'/output/config_00'.$i.'.yml');
    //     }
    // }

    // /**
    //  * Tests the template overriding mechanism when the application defines
    //  * its own custom templates in app/Resources/views/easy_admin/<templateName>.html.twig files
    //  * See EasyAdminExtension::processEntityTemplates().
    //  */
    // public function testApplicationOverridesDefaultTemplates()
    // {
    //     $fixturesDir = __DIR__.'/fixtures/templates/overridden_by_application';

    //     foreach (range(1, 5) as $i) {
    //         $this->parseConfigurationFile($fixturesDir.'/input/admin_00'.$i.'.yml', $fixturesDir);

    //         $this->assertConfigurationParameterMatchesExpectedValue($fixturesDir.'/output/config_00'.$i.'.yml');
    //     }
    // }
}
