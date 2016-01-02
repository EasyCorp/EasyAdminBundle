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

use Symfony\Component\Yaml\Yaml;
use JavierEguiluz\Bundle\EasyAdminBundle\DependencyInjection\EasyAdminExtension;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class EasyAdminConfigurationPassExceptionsTest extends AbstractTestCase
{
    /**
     * @dataProvider provideConfigurationFiles
     */
    public function testBackendConfigurations($configFilePath)
    {
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
}
