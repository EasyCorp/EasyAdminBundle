<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;

class AutocompleteTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->initClient(['environment' => 'autocomplete']);
    }

    /**
     * @dataProvider provideMissingParameters
     */
    public function testAutocompleteWithMissingParameters($query)
    {
        $queryParameters = [
            'action' => 'autocomplete',
            'entity' => 'Category',
            'query' => $query,
        ];

        // remove empty parameters to force the autocomplete error
        $queryParameters = \array_filter($queryParameters);

        $this->getBackendPage($queryParameters);

        $this->assertSame(
            ['results' => []],
            \json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testAutocompleteText()
    {
        $this->getBackendPage([
            'action' => 'autocomplete',
            'entity' => 'Category',
            'query' => 'Parent Categ',
        ]);

        // the results are the first 10 parent categories
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        foreach (\range(1, 10) as $i) {
            $this->assertSame($i, $response['results'][$i - 1]['id']);
            $this->assertSame('Parent Category #'.$i, $response['results'][$i - 1]['text']);
        }
    }

    public function testAutocompleteNumber()
    {
        $this->getBackendPage([
            'action' => 'autocomplete',
            'entity' => 'Category',
            'query' => 21,
        ]);

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(
            [
                ['id' => 21, 'text' => 'Parent Category #21'],
                ['id' => 121, 'text' => 'Category #21'],
            ],
            $response['results']
        );
    }

    public function provideMissingParameters()
    {
        return [
            // query
            [''],
            [null],
        ];
    }
}
