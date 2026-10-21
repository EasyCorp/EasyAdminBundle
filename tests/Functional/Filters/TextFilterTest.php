<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\DatabasePlatformTrait;

class TextFilterTest extends FilterFunctionalTestCase
{
    use DatabasePlatformTrait;

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
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'ALPHA',
            ],
        ]);

        $this->client->request('GET', $url);

        // the filter uses LIKE, which is case-sensitive on PostgreSQL; SQLite (for ASCII
        // characters) and the default collations of MySQL and MariaDB are case-insensitive
        if (self::isPostgreSql()) {
            $this->assertFilteredCount(0);

            return;
        }

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
