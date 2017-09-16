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

class EmptyBackendTest extends AbstractTestCase
{
    public function testNoEntityHasBeenConfigured()
    {
        $this->initClient(array('environment' => 'empty_backend'));
        $this->client->request('GET', '/admin/');

        $this->assertSame(500, $this->client->getResponse()->getStatusCode());
        $this->assertContains('NoEntitiesConfiguredException', $this->client->getResponse()->getContent());
    }
}
