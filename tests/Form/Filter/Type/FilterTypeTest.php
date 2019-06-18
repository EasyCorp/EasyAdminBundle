<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\FilterRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use Symfony\Component\Form\Test\TypeTestCase;

abstract class FilterTypeTest extends TypeTestCase
{
    /** @var FilterRegistry */
    protected $filterRegistry;

    protected function setUp(): void
    {
        parent::setUp();

        // reset counter (only for test purpose)
        $m = new \ReflectionProperty(ComparisonFilterType::class, 'uniqueAliasId');
        $m->setAccessible(true);
        $m->setValue(0);

        $this->filterRegistry = new FilterRegistry([], []);
    }
}
