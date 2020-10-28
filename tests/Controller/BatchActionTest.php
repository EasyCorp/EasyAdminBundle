<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class BatchActionTest extends AbstractTestCase
{
    protected static $options = ['environment' => 'batch_action'];

    public function testBatchActionsForm()
    {
        $crawler = $this->requestListView();

        $this->assertSame('Delete', $crawler->filter('form[name="batch_form"] button[value="delete"]')->text(null, true));

        $this->assertSame('Custom batch action', trim($crawler->filter('form[name="batch_form"] button[value="custom_batch_action"]')->text(null, true)));
        $this->assertSame('fa fa-fw fa-custom-icon', trim($crawler->filter('form[name="batch_form"] button[value="custom_batch_action"] i')->attr('class')));
    }

    public function testBatchActionsCheckboxes()
    {
        $crawler = $this->requestListView();

        $this->assertSame('form-batch-checkbox-all', $crawler->filter('table.datagrid thead th input[type="checkbox"]')->first()->attr('class'));

        $this->assertSame('form-batch-checkbox', $crawler->filter('table.datagrid tbody td input[type="checkbox"]')->first()->attr('class'));
        $this->assertSame('200', $crawler->filter('table.datagrid tbody td input[type="checkbox"]')->first()->attr('value'));
    }
}
