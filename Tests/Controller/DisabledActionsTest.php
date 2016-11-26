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
use Symfony\Component\HttpKernel\Kernel;

class DisabledActionsTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'disabled_actions'));
    }

    public function testAssociationLinksInListView()
    {
        if (2 === Kernel::MAJOR_VERSION && 3 === Kernel::MINOR_VERSION) {
            $this->markTestSkipped('This test is not compatible with Symfony 2.3.');
        }

        $crawler = $this->requestListView('Purchase');

        $this->assertSame(
            'user1',
            trim($crawler->filter('td[data-label="Buyer"]')->first()->html()),
            'The "buyer" field in the "list" view of the "Purchase" item does not contain a link because the "show" action is disabled for the "User" entity.'
        );
    }

    public function testAssociationLinksInShowView()
    {
        if (2 === Kernel::MAJOR_VERSION && 3 === Kernel::MINOR_VERSION) {
            $this->markTestSkipped('This test is not compatible with Symfony 2.3.');
        }

        // 'Purchase' entity 'id' is generated randomly. In order to browse the
        // 'show' view of the first 'Purchase' entity, browse the 'list' view
        // and get the 'id' from the first row of the listing
        $crawler = $this->requestListView('Purchase');
        $firstPurchaseId = trim($crawler->filter('td[data-label="ID"]')->first()->text());
        $crawler = $this->requestShowView('Purchase', $firstPurchaseId);

        $this->assertSame(
            'user1',
            trim($crawler->filter('.field-association:contains("Buyer") .form-control')->first()->html()),
            'The "buyer" field in the "show" view of the "Purchase" item does not contain a link because the "show" action is disabled for the "User" entity.'
        );
    }

    public function testAccessingDisabledActions()
    {
        $crawler = $this->requestShowView('User', 1);

        $this->assertContains(
            'Error: The requested &quot;show&quot; action is not allowed for the &quot;User&quot; entity.',
            $this->client->getResponse()->getContent()
        );
    }
}
