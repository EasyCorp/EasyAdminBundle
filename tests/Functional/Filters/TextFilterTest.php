<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;

class TextFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['alpha test string', 'beta sample text', 'gamma record', 'alphabetical order', 'delta entry', 'this ends with test'] as $text) {
            $this->entities[$text] = FilterTestEntityFactory::createOne(['textFilter' => $text]);
        }
    }

    public function testFilterContains(): void
    {
        // filter for records containing "alpha"
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'alpha',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['alpha test string', 'alphabetical order']);
    }

    public function testFilterContainsPartialMatch(): void
    {
        // filter for records containing "test"
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'test',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['alpha test string', 'this ends with test']);
    }

    public function testFilterNotContains(): void
    {
        // filter for records NOT containing "alpha"
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::NOT_CONTAINS,
                'value' => 'alpha',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntities(['beta sample text', 'gamma record', 'delta entry', 'this ends with test']);
    }

    public function testFilterStartsWith(): void
    {
        // filter for records starting with "alpha"
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::STARTS_WITH,
                'value' => 'alpha',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['alpha test string', 'alphabetical order']);
    }

    public function testFilterEndsWith(): void
    {
        // filter for records ending with "test"
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::ENDS_WITH,
                'value' => 'test',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities(['this ends with test']);
    }

    public function testFilterEquals(): void
    {
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => 'gamma record',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities(['gamma record']);
    }

    public function testFilterNotEquals(): void
    {
        // filter for records NOT equal to 'gamma record'
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => 'gamma record',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntities(['alpha test string', 'beta sample text', 'alphabetical order', 'delta entry', 'this ends with test']);
    }

    public function testFilterCaseInsensitiveContains(): void
    {
        // filter with different case - should still match (database dependent)
        // most databases do case-insensitive LIKE by default
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'ALPHA',
            ],
        ]);

        $this->client->request('GET', $url);

        // this behavior may vary by database
        // SQLite is case-insensitive by default for ASCII characters
        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['alpha test string', 'alphabetical order']);
    }

    public function testFilterNoResults(): void
    {
        // filter for a value that doesn't exist
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'nonexistent_value_xyz',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(0);
    }
}
