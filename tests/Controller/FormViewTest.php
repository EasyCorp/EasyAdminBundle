<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class FormViewTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'form_view']);
    }

    /**
     * @group legacy
     */
    public function testNewView()
    {
        $crawler = $this->requestNewView('Product');

        $this->assertSame('Group Label 1', \trim($crawler->filter('form fieldset legend')->eq(0)->text()));

        $this->assertCount(1, $crawler->filter('form fieldset #product_ean'));
        $this->assertEmpty($crawler->filter('form fieldset input')->eq(0)->attr('required'));

        $this->assertSame('Section Label 1', \trim($crawler->filter('form fieldset .form-section h2')->eq(0)->text()));

        $this->assertCount(1, $crawler->filter('form fieldset #product_name'));

        $this->assertCount(2, $crawler->filter('form fieldset .form-section'));

        $this->assertCount(0, $crawler->filter('form fieldset #product_description'),
            'The description field defined in the form view is removed by the new view.'
        );
    }

    /**
     * @group legacy
     */
    public function testEditView()
    {
        $crawler = $this->requestEditView('Product', 1);

        $this->assertSame('Group Label 1', \trim($crawler->filter('form fieldset legend')->eq(0)->text()));

        $this->assertCount(0, $crawler->filter('form fieldset #product_ean'),
            'The EAN field defined in the form view is removed by the edit view.'
        );

        $this->assertSame('Section Label 1', \trim($crawler->filter('form fieldset .form-section h2')->eq(0)->text()));

        $this->assertCount(1, $crawler->filter('form fieldset #product_name'));
        $this->assertSame('Edit Help', \trim($crawler->filter('form fieldset input + .help-block')->eq(0)->text()));

        $this->assertCount(2, $crawler->filter('form fieldset .form-section'));
        $this->assertCount(1, $crawler->filter('form fieldset #product_description'));
        $this->assertCount(1, $crawler->filter('form fieldset #product_price'));
    }
}
