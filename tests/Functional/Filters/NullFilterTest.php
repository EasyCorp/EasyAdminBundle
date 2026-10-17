<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;

class NullFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $values = ['not null value' => 'not null value', 'first null' => null, 'has value' => 'has value', 'second null' => null];
        foreach ($values as $key => $value) {
            $this->entities[$key] = FilterTestEntityFactory::createOne(['nullFilter' => $value]);
        }
    }

    public function testFilterIsNull(): void
    {
        $url = $this->generateFilteredIndexUrl([
            'nullFilter' => [
                'value' => NullFilter::CHOICE_VALUE_NULL,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['first null', 'second null']);
    }

    public function testFilterIsNotNull(): void
    {
        $url = $this->generateFilteredIndexUrl([
            'nullFilter' => [
                'value' => NullFilter::CHOICE_VALUE_NOT_NULL,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['not null value', 'has value']);
    }
}
