<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\FilterRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Component\Form\Test\TypeTestCase;

abstract class FilterTypeTest extends TypeTestCase
{
    protected const FILTER_TYPE = ComparisonFilterType::class;

    /** @var FilterRegistry */
    protected $filterRegistry;
    /** @var QueryBuilder */
    protected $qb;

    protected function setUp(): void
    {
        parent::setUp();

        // reset counter (only for test purpose)
        $m = new \ReflectionProperty(static::FILTER_TYPE, 'uniqueAliasId');
        $m->setAccessible(true);
        $m->setValue(0);

        $this->filterRegistry = new FilterRegistry([], []);
        $this->qb = $this->createQueryBuilder();
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $em = DoctrineTestHelper::createTestEntityManager();
        $qb = new QueryBuilder($em);
        $qb->select('o')->from('Object', 'o');

        return $qb;
    }
}
