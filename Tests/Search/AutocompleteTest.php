<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Search;

use Symfony\Component\DomCrawler\Crawler;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class AutocompleteTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(array('environment' => 'autocomplete'));
    }

    /**
     * @dataProvider provideMissingParameters
     */
    public function testAutocompleteWithMissingParameters($entity, $property, $view, $query)
    {
        $this->getBackendHomepage();

        $this->assertSame(
            array('results' => array()),
            $this->client->getContainer()->get('easyadmin.autocomplete')->find($entity, $property, $view, $query),
            'Some of the parameters required for autocomplete are missing.'
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The "entity" argument must contain the name of an entity managed by EasyAdmin ("ThisEntityDoesNotExist" given).
     */
    public function testAutocompleteWrongEntity()
    {
        $this->getBackendHomepage();
        $this->client->getContainer()->get('easyadmin.autocomplete')->find('ThisEntityDoesNotExist', 'name', 'edit', 'John Doe');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The "property" argument must contain the name of a property configured in the "edit" view of the "Product" entity ("ThisPropertyDoesNotExist" given).
     */
    public function testAutocompleteWrongProperty()
    {
        $this->getBackendHomepage();
        $this->client->getContainer()->get('easyadmin.autocomplete')->find('Product', 'ThisPropertyDoesNotExist', 'edit', 'John Doe');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The "name" property configured in the "edit" view of the "Product" entity can't be of type "easyadmin_autocomplete" because it's not related to another entity.
     */
    public function testAutocompleteWrongTargetEntity()
    {
        $this->getBackendHomepage();
        $this->client->getContainer()->get('easyadmin.autocomplete')->find('Product', 'name', 'edit', 'John Doe');
    }

    public function testAutocompleteText()
    {
        $this->getBackendHomepage();
        // the query is 'Parent Categ' instead of 'Parent Category' to better test the autocomplete
        $autocomplete = $this->client->getContainer()->get('easyadmin.autocomplete')->find('Category', 'parent', 'edit', 'Parent Categ');

        // the results are the first 15 parent categories
        foreach (range(1, 15) as $i) {
            $this->assertEquals($i, $autocomplete['results'][$i-1]['id']);
            $this->assertEquals('Parent Category #'.$i, $autocomplete['results'][$i-1]['text']);
        }
    }

    public function testAutocompleteNumbers()
    {
        $this->getBackendHomepage();
        $autocomplete = $this->client->getContainer()->get('easyadmin.autocomplete')->find('Category', 'parent', 'edit', 21);

        $this->assertSame(
            array(
                array('id' => 21, 'text' => 'Parent Category #21'),
                array('id' => 121, 'text' => 'Category #21'),
            ),
            $autocomplete['results']
        );
    }

    public function provideMissingParameters()
    {
        return array(
            array('', 'name', 'edit', 'John Doe'),
            array('User', '', 'edit', 'John Doe'),
            array('User', 'name', '', 'John Doe'),
            array('User', 'name', 'edit', ''),
            array(null, 'name', 'edit', 'John Doe'),
            array('User', null, 'edit', 'John Doe'),
            array('User', 'name', null, 'John Doe'),
            array('User', 'name', 'edit', null),
        );
    }
}
