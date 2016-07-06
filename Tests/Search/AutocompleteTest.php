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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "entity" argument must contain the name of an entity managed by EasyAdmin ("ThisEntityDoesNotExist" given).
     */
    public function testAutocompleteWrongEntity()
    {
        $this->getBackendHomepage();
        $this->client->getContainer()->get('easyadmin.autocomplete')->find('ThisEntityDoesNotExist', 'name', 'edit', 'John Doe');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "property" argument must contain the name of a property configured in the "edit" view of the "Product" entity ("ThisPropertyDoesNotExist" given).
     */
    public function testAutocompleteWrongProperty()
    {
        $this->getBackendHomepage();
        $this->client->getContainer()->get('easyadmin.autocomplete')->find('Product', 'ThisPropertyDoesNotExist', 'edit', 'John Doe');
    }

    /**
     * @expectedException \InvalidArgumentException
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

        // the results are the first batch of 10 parent categories
        foreach (range(1, 10) as $i => $n) {
            $this->assertEquals($n, $autocomplete['results'][$i]['id']);
            $this->assertEquals('Parent Category #'.$n, $autocomplete['results'][$i]['text']);
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

    public function testAutocompletePaginator()
    {
        $this->getBackendHomepage();
        // testing page 2
        $autocomplete = $this->client->getContainer()->get('easyadmin.autocomplete')->find('Category', 'parent', 'edit', 'Parent Categ', 2);

        // the results are the second batch of 10 parent categories
        foreach (range(11, 20) as $i => $n) {
            $this->assertEquals($n, $autocomplete['results'][$i]['id']);
            $this->assertEquals('Parent Category #'.$n, $autocomplete['results'][$i]['text']);
        }
    }

    public function provideMissingParameters()
    {
        return array(
            array('', 'parent', 'edit', 'Categ'),
            array('Category', '', 'edit', 'Categ'),
            array('Category', 'parent', '', 'Categ'),
            array('Category', 'parent', 'edit', ''),
            array(null, 'parent', 'edit', 'Categ'),
            array('Category', null, 'edit', 'Categ'),
            array('Category', 'parent', null, 'Categ'),
            array('Category', 'parent', 'edit', null),
        );
    }
}
