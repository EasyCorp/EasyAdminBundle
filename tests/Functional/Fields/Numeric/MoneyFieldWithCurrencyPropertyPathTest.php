<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Numeric;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FieldTestEntityMoneyCurrencyPathCrudController;
use PHPUnit\Framework\Attributes\TestWith;

class MoneyFieldWithCurrencyPropertyPathTest extends AbstractFieldFunctionalTestCase
{
    protected function getControllerFqcn(): string
    {
        return FieldTestEntityMoneyCurrencyPathCrudController::class;
    }

    public function testKnownCurrency(): void
    {
        $entity = $this->createFieldTestEntity([
            'moneyField' => 1250,
            'currencyField' => 'EUR',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());
        $cell = $crawler->filter(sprintf('tr[data-id="%d"] td[data-column="moneyField"]', $entity->getId()));
        static::assertSame('€12.50', $cell->text());
    }

    #[TestWith(['ZZZ'])]
    #[TestWith(['usd'])]
    #[TestWith(['X'])]
    #[TestWith(['<b>'])]
    public function testUnknownStoredCurrency(string $currencyCode): void
    {
        $entity = $this->createFieldTestEntity([
            'moneyField' => 1250,
            'currencyField' => $currencyCode,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());
        static::assertResponseIsSuccessful();
        $cell = $crawler->filter(sprintf('tr[data-id="%d"] td[data-column="moneyField"]', $entity->getId()));
        static::assertSame('12.50 '.$currencyCode, $cell->text());
        static::assertStringNotContainsStringIgnoringCase('<b>', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', $this->generateDetailUrl($entity->getId()));
        static::assertResponseIsSuccessful();
        static::assertStringNotContainsStringIgnoringCase('<b>', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        static::assertResponseIsSuccessful();
        static::assertStringNotContainsStringIgnoringCase('<b>', (string) $this->client->getResponse()->getContent());
    }
}
