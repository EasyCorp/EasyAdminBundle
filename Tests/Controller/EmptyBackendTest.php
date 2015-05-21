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

class EmptyBackendTest extends AbstractTestCase
{
    public function testNoEntityHasBennConfigured()
    {
        $this->initClient(array('environment' => 'empty_backend'));
        $this->getBackendHomepage();

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertEquals("Your backend is empty because you haven't configured\n    any Doctrine entity to manage.", trim($this->client->getCrawler()->filter('body.error .container .error-problem p.lead')->text()));
    }
}
