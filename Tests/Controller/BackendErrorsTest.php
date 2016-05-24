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
        $this->assertContains('Entity "InexistentEntity" is not managed by EasyAdmin.', $crawler->filter('head title')->text());
    }
}
