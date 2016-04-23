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

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Processor;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

class ConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    const CACHED_CONTAINER_PATH = __DIR__.'/../../build/cache/test/AppTestDebugProjectContainer.php';

    /**
     * @dataProvider provideConfigFilePaths
     */
    public function testLoadConfig($backendConfigFilePath, $expectedConfigFilePath)
    {
        if (!$this->isTestCompatible($backendConfigFilePath)) {
            $this->markTestSkipped('This test is not compatible with this Symfony Version.');
        }

        $backendConfig = $this->loadConfig($backendConfigFilePath);
        $expectedConfig = Yaml::parse(file_get_contents($expectedConfigFilePath));

        // 'assertEquals()' is not used because storing the full processed backend
        // configuration would make fixtures too big
        $this->assertArraySubset($expectedConfig['easy_admin'], $backendConfig);
    }

    /**
     * @dataProvider provideConfigExceptionFilePaths
     */
    public function testBackendExceptions($backendConfigFilePath)
    {
        $backendConfig = Yaml::parse(file_get_contents($backendConfigFilePath));
        if (isset($backendConfig['expected_exception']['class'])) {
            if (isset($backendConfig['expected_exception']['message_string'])) {
                $this->setExpectedException($backendConfig['expected_exception']['class'], $backendConfig['expected_exception']['message_string']);
            } elseif (isset($backendConfig['expected_exception']['message_regexp'])) {
                $this->setExpectedExceptionRegExp($backendConfig['expected_exception']['class'], $backendConfig['expected_exception']['message_regexp']);
            }
        }

        // delete the container stored in the cache and which was created after
        // booting the kernel. Otherwise, all the tests will use the cached
        // container and therefore, all of them would use the first loaded config
        @unlink(self::CACHED_CONTAINER_PATH);

        $this->loadConfig($backendConfigFilePath);
    }

    public function provideConfigFilePaths()
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

    public function provideConfigExceptionFilePaths()
    {
        // glob() returns an array of strings and fixtures require an array of arrays
        return array_map(
            function ($filePath) { return array($filePath); },
            glob(__DIR__.'/fixtures/exceptions/*.yml')
        );
    }

    private function isTestCompatible($filePath)
    {
        if (2 != Kernel::MAJOR_VERSION || 3 != Kernel::MINOR_VERSION) {
            return true;
        }

        // these tests are not compatible with Symfony 2.3 because the YAML
        // component of that version does not ignore duplicate keys
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

        return !in_array(substr($filePath, -34), $incompatibleTests);
    }

    /**
     * Given the path of the YAML file which defines the backend config, it
     * fully processes it to generate the real and complete config used by
     * the application.
     *
     * @param string $backendConfigFilePath
     *
     * @return array
     */
    private function loadConfig($backendConfigFilePath)
    {
        // to get the processed config, boot a special Symfony kernel to load
        // the backend config dynamically
        $configuration = Yaml::parse(file_get_contents($backendConfigFilePath));
        $app = new \DynamicConfigLoadingKernel($configuration['easy_admin']);
        $app->boot();

        $backendConfig = $app->getContainer()->get('easyadmin.config.manager')->loadConfig();

        // delete the container stored in the cache and which was created after
        // booting the kernel. Otherwise, all the tests will use the cached
        // container and therefore, all of them would use the first loaded config
        @unlink(self::CACHED_CONTAINER_PATH);

        return $backendConfig;
    }
}
