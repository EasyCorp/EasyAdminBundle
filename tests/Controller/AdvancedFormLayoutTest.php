<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class AdvancedFormLayoutTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'advanced_form_layout'];

    /**
     * @group legacy
     *
     * This test checks that a complex form layout is properly generated for
     * both 'edit' and 'new' forms. Testing very specific CSS selectors and
     * HTML elements is needed to prevent regressions.
     */
    public function testFormLayout()
    {
        // a dataProvider can't be used because it can't create the Crawlers
        foreach (['edit', 'new'] as $view) {
            $queryParams = array_merge(
                ['action' => $view, 'entity' => 'Product'],
                'edit' === $view ? ['id' => 1] : []
            );
            $crawler = $this->getBackendPage($queryParams);

            $this->assertSame(
                'Basic information',
                trim($crawler->filter('ul.nav-tabs li')->eq(0)->text(null, true)),
                'The first tab of the form is displayed correctly.'
            );
            $this->assertContains(
                'fa fa-fw fa-pencil',
                $crawler->filter('ul.nav-tabs li')->eq(0)->filter('i')->attr('class'),
                'The first tab displays the configured icon.'
            );
            $this->assertCount(
                0,
                $crawler->filter('.tab-pane')->eq(0)->filter('.tab-help'),
                'The first tab does not display a help message.'
            );
            $this->assertSame(
                'Extra information',
                trim($crawler->filter('ul.nav-tabs li')->eq(1)->text(null, true)),
                'The second tab of the form is displayed correctly.'
            );
            $this->assertContains(
                'The <b>help message</b> of this tab',
                trim($crawler->filter('.tab-pane')->eq(1)->filter('.tab-help')->html()),
                'The second tab of the form displays a help message.'
            );

            $this->assertSame(
                'product_name',
                $crawler->filter('form .field-group')->eq(0)->filter('fieldset input')->attr('id'),
                'The "name" field is displayed in a "group" created automatically to not have ungrouped form fields.'
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(0)->filter('fieldset input'),
                'The "group" created automatically to not have ungrouped form fields only contains one field.'
            );

            $this->assertSame(
                'field-group col-8',
                trim($crawler->filter('form .field-group')->eq(1)->attr('class'))
            );
            $this->assertSame(
                'Basic information',
                trim($crawler->filter('form .field-group')->eq(1)->filter('fieldset legend')->text(null, true))
            );
            $this->assertSame(
                'fa fa-fw fa-pencil',
                $crawler->filter('form .field-group')->eq(1)->filter('fieldset legend i')->attr('class')
            );
            $this->assertSame(
                'product_description',
                $crawler->filter('form .field-group')->eq(1)->filter('fieldset textarea')->attr('id')
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(1)->filter('fieldset .form-section')
            );
            $this->assertContains(
                '<h2>',
                trim($crawler->filter('form .field-group')->eq(1)->filter('fieldset .form-section')->html())
            );
            $this->assertContains(
                '<span></span>',
                trim($crawler->filter('form .field-group')->eq(1)->filter('fieldset .form-section')->html())
            );
            $this->assertSame(
                'product_categories',
                $crawler->filter('form .field-group')->eq(1)->filter('fieldset select')->attr('id')
            );

            $this->assertSame(
                'field-group col-4',
                trim($crawler->filter('form .field-group')->eq(2)->attr('class'))
            );
            $this->assertSame(
                'Product Details',
                trim($crawler->filter('form .field-group')->eq(2)->filter('fieldset legend')->text(null, true))
            );
            $this->assertCount(
                0,
                $crawler->filter('form .field-group')->eq(2)->filter('fieldset legend i')
            );
            $this->assertSame(
                'product_ean',
                $crawler->filter('form .field-group')->eq(2)->filter('fieldset input')->eq(0)->attr('id')
            );
            $this->assertSame(
                'product_price',
                $crawler->filter('form .field-group')->eq(2)->filter('fieldset input')->eq(1)->attr('id')
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(2)->filter('fieldset .form-section')
            );
            $this->assertSame(
                'Advanced Settings',
                trim($crawler->filter('form .field-group')->eq(2)->filter('fieldset .form-section h2')->text(null, true))
            );
            $this->assertSame(
                'fa fa-fw fa-warning',
                $crawler->filter('form .field-group')->eq(2)->filter('fieldset .form-section i')->attr('class')
            );
            $this->assertSame(
                'Reserved for administrators use',
                trim($crawler->filter('form .field-group')->eq(2)->filter('fieldset .form-section-help')->text(null, true))
            );
            $this->assertSame(
                'product_enabled',
                $crawler->filter('form .field-group')->eq(2)->filter('fieldset input')->eq(2)->attr('id')
            );
            $this->assertSame(
                'product_createdAt_date_month',
                $crawler->filter('form .field-group')->eq(2)->filter('fieldset select')->eq(0)->attr('id')
            );

            $this->assertSame(
                'field-group col-8 w-100',
                trim($crawler->filter('form .field-group')->eq(3)->attr('class'))
            );
            $this->assertCount(
                0,
                $crawler->filter('form .field-group')->eq(3)->filter('fieldset > legend')
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(3)->filter('fieldset div#product_features')
            );

            $this->assertSame(
                'field-group col-4',
                trim($crawler->filter('form .field-group')->eq(4)->attr('class'))
            );
            $this->assertCount(
                0,
                $crawler->filter('form .field-group')->eq(4)->filter('fieldset > legend')
            );
            $this->assertCount(
                1,
                $crawler->filter('form .field-group')->eq(4)->filter('fieldset div#product_tags')
            );

            $this->assertSame(
                'field-group col-4',
                trim($crawler->filter('form .field-group')->eq(5)->attr('class'))
            );
            $this->assertSame(
                'Attachments',
                trim($crawler->filter('form .field-group')->eq(5)->filter('fieldset legend')->text(null, true))
            );
            $this->assertSame(
                'fa fa-fw fa-paperclip',
                $crawler->filter('form .field-group')->eq(5)->filter('fieldset legend i')->attr('class')
            );
            $this->assertSame(
                'PNG format is preferred',
                trim($crawler->filter('form .field-group')->eq(5)->filter('fieldset .legend-help')->text(null, true))
            );
            $this->assertSame(
                'product_image',
                $crawler->filter('form .field-group')->eq(5)->filter('fieldset input')->eq(0)->attr('id')
            );

            $this->assertSame(
                'Save changes',
                trim($crawler->filter('.form-actions button')->eq(0)->text(null, true))
            );
            $this->assertSame(
                'Back to listing',
                trim($crawler->filter('.form-actions a.action-list')->text(null, true))
            );
            if ('edit' === $view) {
                $this->assertSame(
                    'Delete',
                    trim($crawler->filter('.form-actions a.action-delete')->text(null, true))
                );
            }
        }
    }
}
