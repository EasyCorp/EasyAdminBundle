<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\FilterRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use PHPUnit\Framework\AssertionFailedError;
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

    // this is needed because some Doctrine versions changed some internals
    // and we can't use this code: $this->assertEquals($params, $this->qb->getParameters()->toArray());
    // (see https://github.com/doctrine/orm/pull/7528)
    protected function assertSameDoctrineParams(array $expectedParams, array $actualParams)
    {
        for ($i = 0; $i < \count($expectedParams); ++$i) {
            $expectedParam = $expectedParams[$i];
            $actualParam = $actualParams[$i];

            $namesAreDifferent = $expectedParam->getName() !== $actualParam->getName();
            $typesAreDifferent = $expectedParam->getType() !== $actualParam->getType();

            $expectedValue = $expectedParam->getValue();
            $actualValue = $actualParam->getValue();
            if ($expectedValue instanceof \DateInterval) {
                $valuesAreDifferent = !$this->dateIntervalsAreEqual($expectedValue, $actualValue);
            } elseif (\is_object($expectedValue)) {
                $valuesAreDifferent = $expectedParam->getValue() != $actualParam->getValue();
            } else {
                $valuesAreDifferent = $expectedParam->getValue() !== $actualParam->getValue();
            }

            if ($namesAreDifferent || $valuesAreDifferent || $typesAreDifferent) {
                throw new AssertionFailedError(sprintf('The "%s" and "%s" Doctrine parameters are not the same.', $expectedParam->getName(), $actualParam->getName()));
            }
        }
    }

    private function dateIntervalsAreEqual(\DateInterval $a, \DateInterval $b): bool
    {
        $startDate1 = date_create();
        $startDate2 = clone $startDate1;

        $endDate1 = $startDate1->add($a);
        $endDate2 = $startDate2->add($b);

        return $endDate1 == $endDate2;
    }
}
