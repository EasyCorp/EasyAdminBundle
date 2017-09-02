<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Configuration;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

class ConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    public static function tearDownAfterClass()
    {
        // this is important because this test generates a different Symfony
        // kernel for each configuration to avoid cache issues
        self::deleteDirectory(__DIR__.'/../../build/cache/test');
    }

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
            function ($filePath) {
                return array($filePath);
            },
            glob(__DIR__.'/fixtures/exceptions/*.yml')
        );
    }

    private function isTestCompatible($filePath)
    {
        $testsWithDuplicatedYamlKeys = array(
            __DIR__.'/fixtures/configurations/input/admin_007.yml',
            __DIR__.'/fixtures/configurations/input/admin_008.yml',
            __DIR__.'/fixtures/configurations/input/admin_013.yml',
            __DIR__.'/fixtures/configurations/input/admin_014.yml',
            __DIR__.'/fixtures/configurations/input/admin_020.yml',
            __DIR__.'/fixtures/configurations/input/admin_021.yml',
            __DIR__.'/fixtures/configurations/input/admin_026.yml',
        );

        // In Symfony 2.3, the YAML component behaves differently than other versions
        // when it founds duplicated keys. In Symfony >= 3.2, duplicated keys are deprecated
        $isSymfony23 = 2 === (int) Kernel::MAJOR_VERSION && 3 === (int) Kernel::MINOR_VERSION;
        $isSymfony32OrNewer = (int) Kernel::VERSION_ID >= 30200;

        if ($isSymfony23 || $isSymfony32OrNewer) {
            return !in_array($filePath, $testsWithDuplicatedYamlKeys);
        }

        return true;
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
        $configuration = Yaml::parse(file_get_contents($backendConfigFilePath));

        // to get the processed config, boot a special Symfony kernel to load
        // the backend config dynamically
        include_once __DIR__.'/../Fixtures/App/DynamicConfigLoadingKernel.php';
        $app = new \DynamicConfigLoadingKernel($configuration['easy_admin']);
        $app->boot();

        $backendConfig = $app->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        return $backendConfig;
    }

    /**
     * Utility method because PHP doesn't allow to delete non-empty directories.
     */
    private static function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $fileinfo->isDir() ? rmdir($fileinfo->getRealPath()) : unlink($fileinfo->getRealPath());
        }

        rmdir($dir);
    }
}
