<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\FormLayout;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FormFieldValueSyntheticCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;

/**
 * Tests for field value formatting using formatValue() method.
 * Verifies that formatValue() receives the original field value,
 * not the one modified with other options (e.g. max length).
 */
class FormFieldValueFormattingTest extends AbstractCrudTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        // clear all existing entities to ensure test isolation
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete(FormTestEntity::class, 'e')->getQuery()->execute();
        $this->entityManager->clear();
    }

    protected function getControllerFqcn(): string
    {
        return FormFieldValueSyntheticCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldsFormatValue(): void
    {
        $entity = new FormTestEntity();
        $entity->setName('Test Entity Name');
        $entity->setCreatedAt(new \DateTime('2020-11-01 09:00:00'));
        $entity->setPriority(42);
        $entity->setPriceInCents(1299); // represents $12.99
        $entity->setScore(1234.5678901234); // high precision float
        $entity->setStatus('active');
        $entity->setDescription(null); // explicitly test null handling
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->client->request('GET', $this->generateIndexUrl());

        // 1. TextField with maxLength - formatValue() receives full string, not truncated "Te…"
        static::assertSelectorTextSame('td[data-column="name"]', 'Test Entity Name',
            'TextField formatValue() should receive full original string, not truncated by maxLength');

        // 2. DateTimeField with format - formatValue() receives DateTime object, not formatted string
        static::assertSelectorTextSame('td[data-column="createdAt"]', '20201101090000',
            'DateTimeField formatValue() should receive DateTime object, not pre-formatted string');

        // 3. IntegerField with number formatting - formatValue() receives raw integer 42, not formatted "00042"
        static::assertSelectorTextSame('td[data-column="priority"]', 'RAW:42',
            'IntegerField formatValue() should receive raw integer, not number-formatted string');

        // 4. MoneyField with divisor (cents) - formatValue() receives original cents value, not divided dollar amount
        static::assertSelectorTextSame('td[data-column="priceInCents"]', 'CENTS:1299',
            'MoneyField formatValue() should receive original cents value (1299), not converted dollar amount (12.99)');

        // 5. NumberField with decimals - formatValue() receives full precision float, not rounded
        static::assertSelectorTextSame('td[data-column="score"]', 'FULL:1234.5678901234',
            'NumberField formatValue() should receive full precision float, not rounded/formatted value');

        // 6. ChoiceField with choices - formatValue() receives raw choice value, not label
        static::assertSelectorTextSame('td[data-column="status"]', 'CHOICE:active',
            'ChoiceField formatValue() should receive raw value "active", not label "Active"');

        // 7. TextField with null - formatValue() receives null, not empty string or default
        static::assertSelectorTextSame('td[data-column="description"]', 'IS_NULL',
            'TextField formatValue() should receive null for null values, not empty string');

        // 8. IdField - formatValue() receives raw ID value
        $expectedIdText = 'ID:'.$entity->getId();
        static::assertSelectorTextSame('td[data-column="id"]', $expectedIdText,
            'IdField formatValue() should receive raw integer ID value');
    }
}
