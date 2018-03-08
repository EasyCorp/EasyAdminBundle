<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class CustomEntityControllerTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'custom_entity_controller']);
    }

    public function testListAction()
    {
        $this->requestListView();
        $this->assertContains('Overridden list action.', $this->client->getResponse()->getContent());
    }

    public function testShowAction()
    {
        $this->requestShowView();
        $this->assertContains('Overridden show action.', $this->client->getResponse()->getContent());
    }

    /**
     * @group legacy
     * @expectedDeprecation The %s method is deprecated since EasyAdmin 1.x and will be removed in 2.0. Use %s instead
     */
    public function testDeprecatedPrePersistEntityMethod()
    {
        $crawler = $this->requestNewView();
        $this->client->followRedirects();

        $categoryName = sprintf('The New Category %s', md5(mt_rand()));
        $form = $crawler->selectButton('Save changes')->form(array(
            'category[name]' => $categoryName,
        ));
        $this->client->submit($form);
    }

    /**
     * @group legacy
     * @expectedDeprecation The %s method is deprecated since EasyAdmin 1.x and will be removed in 2.0. Use %s instead
     */
    public function testDeprecatedPreUpdateEntityMethod()
    {
        $crawler = $this->requestEditView();
        $this->client->followRedirects();

        $categoryName = sprintf('Modified Category %s', md5(mt_rand()));
        $form = $crawler->selectButton('Save changes')->form(array(
            'category[name]' => $categoryName,
        ));
        $this->client->submit($form);
    }

    /**
     * @group legacy
     * @expectedDeprecation The %s method is deprecated since EasyAdmin 1.x and will be removed in 2.0. Use %s instead
     */
    public function testDeprecatedPreRemoveEntityMethod()
    {
        $crawler = $this->requestEditView();
        $form = $crawler->filter('#delete_form_submit')->form();
        $this->client->submit($form);
    }

    /**
     * @group legacy
     * @expectedDeprecation The %s method is deprecated since EasyAdmin 1.x and will be removed in 2.0. Use %s instead
     */
    public function testDeprecatedPrePersistCategory2EntityMethod()
    {
        $crawler = $this->requestNewView('Category2');
        $this->client->followRedirects();

        $categoryName = sprintf('The New Category %s', md5(mt_rand()));
        $form = $crawler->selectButton('Save changes')->form(array(
            'category2[name]' => $categoryName,
        ));
        $this->client->submit($form);
    }

    /**
     * @group legacy
     * @expectedDeprecation The %s method is deprecated since EasyAdmin 1.x and will be removed in 2.0. Use %s instead
     */
    public function testDeprecatedPreUpdateCategory2EntityMethod()
    {
        $crawler = $this->requestEditView('Category2');
        $this->client->followRedirects();

        $categoryName = sprintf('Modified Category %s', md5(mt_rand()));
        $form = $crawler->selectButton('Save changes')->form(array(
            'category2[name]' => $categoryName,
        ));
        $this->client->submit($form);
    }

    /**
     * @group legacy
     * @expectedDeprecation The %s method is deprecated since EasyAdmin 1.x and will be removed in 2.0. Use %s instead
     */
    public function testDeprecatedPreRemoveCategory2EntityMethod()
    {
        $crawler = $this->requestEditView('Category2');
        $form = $crawler->filter('#delete_form_submit')->form();
        $this->client->submit($form);
    }
}
