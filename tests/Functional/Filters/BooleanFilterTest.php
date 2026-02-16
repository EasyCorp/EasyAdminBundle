<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

/**
 * Fixture data (booleanFilter field):
 * - ID 1: true
 * - ID 2: false
 * - ID 3: true
 * - ID 4: false
 * - ID 5: true
 * - ID 6: null
 */
class BooleanFilterTest extends FilterFunctionalTestCase
{
    public function testFilterTrue(): void
    {
        // filter for booleanFilter = true
        // should match: ID 1, ID 3, ID 5
        $url = $this->generateFilteredIndexUrl([
            'booleanFilter' => [
                'value' => '1',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([1, 3, 5]);
    }

    public function testFilterFalse(): void
    {
        // filter for booleanFilter = false
        // should match: ID 2, ID 4
        $url = $this->generateFilteredIndexUrl([
            'booleanFilter' => [
                'value' => '0',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([2, 4]);
    }
}
