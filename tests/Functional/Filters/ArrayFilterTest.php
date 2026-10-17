<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

class ArrayFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $tagSets = [
            'tag1+tag2' => ['tag1', 'tag2'],
            'tag2+tag3' => ['tag2', 'tag3'],
            'tag1' => ['tag1'],
            'tag3' => ['tag3'],
            'tag1+tag2+tag3' => ['tag1', 'tag2', 'tag3'],
            'no tags' => [],
        ];
        foreach ($tagSets as $key => $tags) {
            $this->entities[$key] = FilterTestEntityFactory::createOne(['arrayFilter' => $tags]);
        }
    }

    /**
     * @param list<string> $expectedEntities
     */
    #[DataProvider('containsSingleValueProvider')]
    public function testFilterContainsSingleValue(string $tag, array $expectedEntities): void
    {
        $url = $this->generateFilteredIndexUrl([
            'arrayFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => [$tag],
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedEntities));
        $this->assertVisibleEntities($expectedEntities);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function containsSingleValueProvider(): iterable
    {
        yield 'matches tag1' => ['tag1', ['tag1+tag2', 'tag1', 'tag1+tag2+tag3']];
        yield 'matches tag2' => ['tag2', ['tag1+tag2', 'tag2+tag3', 'tag1+tag2+tag3']];
        yield 'matches tag3' => ['tag3', ['tag2+tag3', 'tag3', 'tag1+tag2+tag3']];
    }

    public function testFilterNotContains(): void
    {
        // filter for arrayFilter NOT containing 'tag1'
        $url = $this->generateFilteredIndexUrl([
            'arrayFilter' => [
                'comparison' => ComparisonType::NOT_CONTAINS,
                'value' => ['tag1'],
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities(['tag2+tag3', 'tag3', 'no tags']);
    }

    /**
     * @param list<string> $tags
     * @param list<string> $expectedEntities
     */
    #[DataProvider('containsAllProvider')]
    public function testFilterContainsAll(array $tags, array $expectedEntities): void
    {
        $url = $this->generateFilteredIndexUrl([
            'arrayFilter' => [
                'comparison' => ComparisonType::CONTAINS_ALL,
                'value' => $tags,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedEntities));
        $this->assertVisibleEntities($expectedEntities);
    }

    /**
     * @return iterable<string, array{list<string>, list<string>}>
     */
    public static function containsAllProvider(): iterable
    {
        yield 'matches tag1 and tag2' => [['tag1', 'tag2'], ['tag1+tag2', 'tag1+tag2+tag3']];
        yield 'matches all three tags' => [['tag1', 'tag2', 'tag3'], ['tag1+tag2+tag3']];
    }
}
