<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;

class BooleanFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $values = ['first true' => true, 'first false' => false, 'second true' => true, 'second false' => false, 'null' => null];
        foreach ($values as $key => $value) {
            $this->entities[$key] = FilterTestEntityFactory::createOne(['booleanFilter' => $value]);
        }
    }

    public function testFilterTrue(): void
    {
        $url = $this->generateFilteredIndexUrl([
            'booleanFilter' => [
                'value' => '1',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['first true', 'second true']);
    }

    public function testFilterFalse(): void
    {
        $url = $this->generateFilteredIndexUrl([
            'booleanFilter' => [
                'value' => '0',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['first false', 'second false']);
    }
}
