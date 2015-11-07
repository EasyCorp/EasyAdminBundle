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

class BackendErrorsTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'default_backend'));
    }

    public function testUndefinedEntityError()
    {
        $crawler = $this->getBackendPage(array(
            'entity' => 'InexistentEntity',
            'view' => 'list',
        ));

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Error: The "InexistentEntity" entity is not defined in the configuration of your backend. Solution: Open your "app/config/config.yml" file and add the "InexistentEntity" entity to the list of entities managed by EasyAdmin.', $crawler->filter('head title')->text());
    }
}
