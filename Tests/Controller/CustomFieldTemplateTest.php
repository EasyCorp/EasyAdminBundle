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

use Symfony\Component\DomCrawler\Crawler;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomFieldTemplateTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'custom_field_template'));
    }

    public function testListViewCustomFieldTemplate()
    {
        $crawler = $this->requestListView();

        $this->assertContains('Custom template for "name" field in the "list" view.', $crawler->filter('#main table td[data-label="Name"]')->eq(0)->text());
        $this->assertContains('The value of the custom option is "custom_list_value".', $crawler->filter('#main table td[data-label="Name"]')->eq(0)->text());

        $parsedConfiguration = $this->client->getKernel()->getContainer()->getParameter('easyadmin.config');
        $this->assertEquals('easy_admin/custom_field_template.html.twig', $parsedConfiguration['entities']['Category']['list']['fields']['name']['template']);
    }

    public function testShowViewCustomFieldTemplate()
    {
        $crawler = $this->requestShowView();

        $this->assertContains('Custom template for "name" field in the "show" view.', $crawler->filter('#main .form-control')->eq(0)->text());
        $this->assertContains('The value of the custom option is "custom_show_value".', $crawler->filter('#main .form-control')->eq(0)->text());
    }

    public function testListViewCustomFieldTemplateWrongName()
    {
        $crawler = $this->requestListView();

        $this->assertContains('Custom template for "id" field in the "list" view.', $crawler->filter('#main table td[data-label="ID"]')->eq(0)->text());

        $parsedConfiguration = $this->client->getKernel()->getContainer()->getParameter('easyadmin.config');
        $this->assertEquals('easy_admin/custom_field_template.html.twig', $parsedConfiguration['entities']['Category']['list']['fields']['id']['template']);
    }

    public function testShowViewCustomFieldTemplateWrongName()
    {
        $crawler = $this->requestShowView();

        $this->assertContains('Custom template for "id" field in the "show" view.', $crawler->filter('#main .form-control')->eq(1)->text());
    }

    /**
     * @return Crawler
     */
    private function requestListView()
    {
        return $this->getBackendPage(array(
            'action' => 'list',
            'entity' => 'Category',
            'view' => 'list',
        ));
    }

    /**
     * @return Crawler
     */
    private function requestShowView()
    {
        return $this->getBackendPage(array(
            'action' => 'show',
            'entity' => 'Category',
            'id' => '200',
        ));
    }
}
