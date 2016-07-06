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
    public function testAutocompleteWithMissingParameters($property, $view, $query)
    {
        $queryParameters = array(
            'action' => 'autocomplete',
            'entity' => 'Category',
            'property' => $property,
            'view' => $view,
            'query' => $query,
        );

        // remove empty parameters to force the autocomplete error
        $queryParameters = array_filter($queryParameters);

        $this->getBackendPage($queryParameters);

        $this->assertSame(
            array('results' => array()),
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testAutocompleteText()
    {
        $this->getBackendPage(array(
            'action' => 'autocomplete',
            'entity' => 'Category',
            'property' => 'parent',
            'view' => 'edit',
            'query' => 'Parent Categ',
        ));

        // the results are the first 10 parent categories
        $response = json_decode($this->client->getResponse()->getContent(), true);
        foreach (range(1, 10) as $i) {
            $this->assertEquals($i, $response['results'][$i - 1]['id']);
            $this->assertEquals('Parent Category #'.$i, $response['results'][$i - 1]['text']);
        }
    }

    public function testAutocompleteNumber()
    {
        $this->getBackendPage(array(
            'action' => 'autocomplete',
            'entity' => 'Category',
            'property' => 'parent',
            'view' => 'edit',
            'query' => 21,
        ));

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(
            array(
                array('id' => 21, 'text' => 'Parent Category #21'),
                array('id' => 121, 'text' => 'Category #21'),
            ),
            $response['results']
        );
    }

    public function provideMissingParameters()
    {
        return array(
            // property, view, query
            array('', 'edit', 'Categ'),
            array('parent', '', 'Categ'),
            array('parent', 'edit', ''),
            array(null, 'edit', 'Categ'),
            array('parent', null, 'Categ'),
            array('parent', 'edit', null),
        );
    }
}
