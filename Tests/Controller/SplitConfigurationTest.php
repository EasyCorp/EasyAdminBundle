<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class SplitConfigurationTest extends AbstractTestCase
{
    public function testConfigurationInDifferentFiles()
    {
        $this->initClient(array('environment' => 'split_configuration'));
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $this->assertEquals(array('Category', 'Product'), array_keys($backendConfig['entities']));

        $this->assertEquals('Categories', $backendConfig['entities']['Category']['label']);

        $this->assertEquals('Second Site Name', $backendConfig['site_name']);
        $this->assertEquals('blue', $backendConfig['design']['brand_color']);
    }

    public function testConfigurationErrorsInDifferentFiles()
    {
        $this->setExpectedException(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            'The value "wrong_value" is not allowed for path "easy_admin.design.color_scheme". Permissible values: "dark", "light"'
        );

        $this->initClient(array('environment' => 'split_configuration_error'));
    }
}
