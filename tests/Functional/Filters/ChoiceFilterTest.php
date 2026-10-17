<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

class ChoiceFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['option_a', 'option_b', 'option_c'] as $option) {
            $this->entities['first '.$option] = FilterTestEntityFactory::createOne(['choiceFilter' => $option]);
        }
        foreach (['option_a', 'option_b', 'option_c'] as $option) {
            $this->entities['second '.$option] = FilterTestEntityFactory::createOne(['choiceFilter' => $option]);
        }
    }

    /**
     * @param list<string> $expectedEntities
     */
    #[DataProvider('equalsProvider')]
    public function testFilterEquals(string $option, array $expectedEntities): void
    {
        $url = $this->generateFilteredIndexUrl([
            'choiceFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => $option,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedEntities));
        $this->assertVisibleEntities($expectedEntities);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function equalsProvider(): iterable
    {
        yield 'matches option_a' => ['option_a', ['first option_a', 'second option_a']];
        yield 'matches option_b' => ['option_b', ['first option_b', 'second option_b']];
        yield 'matches option_c' => ['option_c', ['first option_c', 'second option_c']];
    }

    public function testFilterNotEquals(): void
    {
        $url = $this->generateFilteredIndexUrl([
            'choiceFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => 'option_a',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntities(['first option_b', 'first option_c', 'second option_b', 'second option_c']);
    }

    public function testFilterIsNull(): void
    {
        $this->entities['no option'] = FilterTestEntityFactory::createOne(['choiceFilter' => null]);

        // when value is empty, the filter uses IS NULL comparison
        $url = $this->generateFilteredIndexUrl([
            'choiceFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities(['no option']);
    }
}
