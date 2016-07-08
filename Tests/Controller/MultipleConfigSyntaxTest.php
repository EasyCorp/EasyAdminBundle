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

class MultipleConfigSyntaxTest extends AbstractTestCase
{
    public function testConfigurationInDifferentFiles()
    {
        $this->initClient(array('environment' => 'multiple_config_syntax'));
        $backendConfig = $this->client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $expectedEntityNames = array(
            'Product', 'Product2', 'Product3', 'Product4', 'Inventory', 'Product22', 'Product5', 'Inventory2',
        );

        $i = 0;
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $this->assertEquals($expectedEntityNames[$i], $entityName);
            $this->assertEquals($expectedEntityNames[$i], $entityConfig['label']);
            $this->assertEquals('AppTestBundle\Entity\FunctionalTests\Product', $entityConfig['class']);

            ++$i;
        }
    }
}
