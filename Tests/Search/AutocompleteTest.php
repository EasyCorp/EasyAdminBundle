<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Search;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

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
    public function testAutocompleteWithMissingParameters($entity, $query)
    {
        $this->getBackendHomepage();

        $this->assertSame(
            array('results' => array()),
            $this->client->getContainer()->get('easyadmin.autocomplete')->find($entity, $query),
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
        $this->client->getContainer()->get('easyadmin.autocomplete')->find('ThisEntityDoesNotExist', 'John Doe');
    }

    public function testAutocompleteText()
    {
        $this->getBackendHomepage();
        // the query is 'Parent Categ' instead of 'Parent Category' to better test the autocomplete
        $autocomplete = $this->client->getContainer()->get('easyadmin.autocomplete')->find('Category', 'Parent Categ');

        // the results are the first batch of 10 parent categories
        foreach (range(1, 10) as $i => $n) {
            $this->assertSame($n, $autocomplete['results'][$i]['id']);
            $this->assertSame('Parent Category #'.$n, $autocomplete['results'][$i]['text']);
        }
    }

    public function testAutocompleteNumbers()
    {
        $this->getBackendHomepage();
        $autocomplete = $this->client->getContainer()->get('easyadmin.autocomplete')->find('Category', 21);

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
        $autocomplete = $this->client->getContainer()->get('easyadmin.autocomplete')->find('Category', 'Parent Categ', 2);

        // the results are the second batch of 10 parent categories
        foreach (range(11, 20) as $i => $n) {
            $this->assertSame($n, $autocomplete['results'][$i]['id']);
            $this->assertSame('Parent Category #'.$n, $autocomplete['results'][$i]['text']);
        }
    }

    public function provideMissingParameters()
    {
        return array(
            array('', 'Categ'),
            array(null, 'Categ'),
            array('Category', ''),
            array('Category', null),
        );
    }
}
