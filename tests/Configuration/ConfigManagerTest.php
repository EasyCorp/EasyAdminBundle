<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ConfigManagerTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        // this is important because this test generates a different Symfony
        // kernel for each configuration to avoid cache issues
        self::deleteDirectory(__DIR__.'/../../build/cache/test');
    }

    /**
     * @group legacy
     * @dataProvider provideConfigFilePaths
     */
    public function testLoadConfig($backendConfigFilePath, $expectedConfigFilePath)
    {
        $backendConfig = $this->loadConfig($backendConfigFilePath);
        $expectedConfig = Yaml::parse(\file_get_contents($expectedConfigFilePath));

        $this->assertArraySubset($expectedConfig['easy_admin'], $backendConfig);
    }

    /**
     * @group legacy
     * @dataProvider provideConfigExceptionFilePaths
     */
    public function testBackendExceptions($backendConfigFilePath)
    {
        $backendConfig = Yaml::parse(\file_get_contents($backendConfigFilePath));
        if (isset($backendConfig['expected_exception']['class'])) {
            $this->expectException($backendConfig['expected_exception']['class']);
            if (isset($backendConfig['expected_exception']['message_string'])) {
                $this->expectException($backendConfig['expected_exception']['class']);
                $this->expectExceptionMessage($backendConfig['expected_exception']['message_string']);
            } elseif (isset($backendConfig['expected_exception']['message_regexp'])) {
                $this->expectException($backendConfig['expected_exception']['class']);
                $this->expectExceptionMessageRegExp($backendConfig['expected_exception']['message_regexp']);
            }
        }

        $this->loadConfig($backendConfigFilePath);
    }

    public function provideConfigFilePaths()
    {
        $inputs = \glob(__DIR__.'/fixtures/configurations/input/admin_*.yml');
        $outputs = \glob(__DIR__.'/fixtures/configurations/output/config_*.yml');

        return \array_map(null, $inputs, $outputs);
    }

    public function provideConfigExceptionFilePaths()
    {
        // glob() returns an array of strings and fixtures require an array of arrays
        return \array_map(
            function ($filePath) {
                return [$filePath];
            },
            \glob(__DIR__.'/fixtures/exceptions/*.yml')
        );
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
        $configuration = Yaml::parse(\file_get_contents($backendConfigFilePath));

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
        if (!\is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $fileinfo->isDir() ? \rmdir($fileinfo->getRealPath()) : \unlink($fileinfo->getRealPath());
        }

        \rmdir($dir);
    }
}
