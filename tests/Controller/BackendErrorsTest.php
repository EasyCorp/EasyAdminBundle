<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

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

        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
        $this->assertContains('The "InexistentEntity" entity is not defined in the configuration of your backend.', $crawler->filter('head title')->text());
    }
}
