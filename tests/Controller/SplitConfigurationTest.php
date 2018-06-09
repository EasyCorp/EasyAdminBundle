<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class SplitConfigurationTest extends AbstractTestCase
{
    public function testConfigurationInDifferentFiles()
    {
        $this->initClient(['environment' => 'split_configuration']);
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $this->assertSame(['Category', 'Product'], array_keys($backendConfig['entities']));

        $this->assertSame('Categories', $backendConfig['entities']['Category']['label']);

        $this->assertSame('Second Site Name', $backendConfig['site_name']);
        $this->assertSame('blue', $backendConfig['design']['brand_color']);
    }

    public function testConfigurationErrorsInDifferentFiles()
    {
        $this->expectException(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            'The value "wrong_value" is not allowed for path "easy_admin.design.color_scheme". Permissible values: "dark", "light"'
        );

        $this->initClient(['environment' => 'split_configuration_error']);
    }
}
