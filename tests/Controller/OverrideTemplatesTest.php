<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class OverrideTemplatesTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'override_templates'];

    public function testConfigurationOfOverriddenTemplates()
    {
        $backendConfig = static::$client->getContainer()->get('easyadmin.config.manager')->getBackendConfig();

        $this->assertSame('@EasyAdmin/default/new.html.twig', $backendConfig['design']['templates']['new']);
        $this->assertSame('override_templates/show.html.twig', $backendConfig['design']['templates']['show']);

        $entityConfig = static::$client->getContainer()->get('easyadmin.config.manager')->getEntityConfig('Category');

        $this->assertSame('@EasyAdmin/default/new.html.twig', $entityConfig['templates']['new']);
        $this->assertSame('override_templates/show.html.twig', $entityConfig['templates']['show']);
        $this->assertSame('override_templates/list.html.twig', $entityConfig['templates']['list']);
    }

    public function testTemplatesOverriddenByGlobalConfiguration()
    {
        $this->requestShowView();

        $this->assertContains(
            'Simple template used to override the default show.html.twig template.',
            static::$client->getResponse()->getContent()
        );
    }

    public function testTemplatesOverriddenByEntityConfiguration()
    {
        $this->requestListView();

        $this->assertContains(
            'Simple template used to override the default list.html.twig template.',
            static::$client->getResponse()->getContent()
        );
    }

    public function testTemplatesOverriddenBySymfonyOverridingMechanism()
    {
        $this->requestNewView();

        $this->assertContains(
            'Overridden using Symfony\'s template overriding mechanism',
            static::$client->getResponse()->getContent()
        );
    }
}
