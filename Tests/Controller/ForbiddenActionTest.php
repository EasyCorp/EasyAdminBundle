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
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\NoEntitiesConfiguredException;

class ForbiddenActionTest extends AbstractTestCase
{
    public function testRequestedActionIsForbidden()
    {
        $this->initClient(array('environment' => 'forbidden_action'));
        $this->client->request('GET', '/admin/?action=new&entity=Category&view=list');

        $this->assertEquals(500, $this->client->getResponse()->getStatusCode());
        $this->assertContains('The requested <code>new</code> action is not allowed.', $this->client->getResponse()->getContent());
    }
}
