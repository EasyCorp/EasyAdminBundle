<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Configuration;

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class AdminControllerTest extends AbstractTestCase
{
    public function testNoEntityInBackend()
    {
        $this->initClient(array('environment' => 'empty'));
        $this->client->request('GET', '/admin');

        $this->assertEquals(301, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('http://localhost/admin/', $this->client->getResponse()->headers->get('Location'));

        $crawler = $this->client->followRedirect();

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertEquals("Your backend is empty because you haven't configured\n    any Doctrine entity to manage.", trim($crawler->filter('body.error .container .error-problem p.lead')->text()));
    }
}
