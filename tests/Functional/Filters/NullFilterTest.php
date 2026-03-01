<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;

/**
 * Fixture data (nullFilter field):
 * - ID 1: 'not null value'
 * - ID 2: 'another value'
 * - ID 3: null
 * - ID 4: null
 * - ID 5: 'has value'
 * - ID 6: 'value exists'
 */
class NullFilterTest extends FilterFunctionalTestCase
{
    public function testFilterIsNull(): void
    {
        // filter for nullFilter IS NULL
        // should match: ID 3, ID 4
        $url = $this->generateFilteredIndexUrl([
            'nullFilter' => [
                'value' => NullFilter::CHOICE_VALUE_NULL,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([3, 4]);
    }

    public function testFilterIsNotNull(): void
    {
        // filter for nullFilter IS NOT NULL
        // should match: ID 1, ID 2, ID 5, ID 6
        $url = $this->generateFilteredIndexUrl([
            'nullFilter' => [
                'value' => NullFilter::CHOICE_VALUE_NOT_NULL,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntityIds([1, 2, 5, 6]);
    }
}
