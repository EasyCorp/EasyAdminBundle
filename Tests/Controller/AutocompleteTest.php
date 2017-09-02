<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

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
    public function testAutocompleteWithMissingParameters($query)
    {
        $queryParameters = array(
            'action' => 'autocomplete',
            'entity' => 'Category',
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
            'query' => 'Parent Categ',
        ));

        // the results are the first 10 parent categories
        $response = json_decode($this->client->getResponse()->getContent(), true);
        foreach (range(1, 10) as $i) {
            $this->assertSame($i, $response['results'][$i - 1]['id']);
            $this->assertSame('Parent Category #'.$i, $response['results'][$i - 1]['text']);
        }
    }

    public function testAutocompleteNumber()
    {
        $this->getBackendPage(array(
            'action' => 'autocomplete',
            'entity' => 'Category',
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
            // query
            array(''),
            array(null),
        );
    }
}
