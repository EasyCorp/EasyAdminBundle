<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class MultipleConfigSyntaxTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'multiple_config_syntax'];

    public function testConfigurationInDifferentFiles()
    {
        $backendConfig = static::$client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $expectedEntityNames = [
            'Product', 'Product2', 'Product3', 'Product4', 'Inventory', 'Product22', 'Product5', 'Inventory2',
        ];

        $i = 0;
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $this->assertSame($expectedEntityNames[$i], $entityName);
            $this->assertSame($expectedEntityNames[$i], $entityConfig['label']);
            $this->assertSame('AppTestBundle\Entity\FunctionalTests\Product', $entityConfig['class']);

            ++$i;
        }
    }
}
