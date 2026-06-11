<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class TelephoneFieldTest extends AbstractFieldFunctionalTest
{
    public function testTelephoneFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'telephoneField' => '+1-555-123-4567',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $telephoneFieldCell = $entityRow->filter('td[data-column="telephoneField"]');
        static::assertStringContainsString('+1-555-123-4567', $telephoneFieldCell->text());
    }

    public function testTelephoneFieldDisplaysAsLinkOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'telephoneField' => '+1-555-987-6543',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $telLink = $entityRow->filter('td[data-column="telephoneField"] a[href^="tel:"]');
        static::assertCount(1, $telLink, 'Telephone field should render as tel link');
        static::assertSame('tel:%2B1-555-987-6543', $telLink->attr('href'));
    }

    public function testTelephoneFieldEncodesSchemeInjectionPayload(): void
    {
        $payload = '+1-555;phone-context=evil';
        $entity = $this->createFieldTestEntity([
            'telephoneField' => $payload,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $telLink = $entityRow->filter('td[data-column="telephoneField"] a[href^="tel:"]');
        static::assertCount(1, $telLink, 'Telephone field should render as tel link');

        $href = $telLink->attr('href');
        static::assertStringNotContainsString(';', $href, 'Raw ";" must not leak into href');
        static::assertSame('tel:'.rawurlencode($payload), $href);
    }

    /**
     * @dataProvider validE164NumbersProvider
     */
    public function testTelephoneFieldRendersValidE164Numbers(string $phoneNumber): void
    {
        $entity = $this->createFieldTestEntity([
            'telephoneField' => $phoneNumber,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $telLink = $entityRow->filter('td[data-column="telephoneField"] a[href^="tel:"]');
        static::assertCount(1, $telLink, sprintf('Telephone field should render as tel link for "%s"', $phoneNumber));
        static::assertSame('tel:'.rawurlencode($phoneNumber), $telLink->attr('href'));
        static::assertStringContainsString($phoneNumber, $telLink->text(), 'Visible label should preserve the original number');
    }

    public static function validE164NumbersProvider(): iterable
    {
        // E.164 example numbers sourced from Google libphonenumber metadata
        yield 'US' => ['+1 201-555-0123'];
        yield 'UK' => ['+44 121 234 5678'];
        yield 'Germany' => ['+49 30 12345678'];
        yield 'Japan' => ['+81 3-1234-5678'];
        yield 'Brazil' => ['+55 11 99999-8888'];
    }

    public function testTelephoneFieldDisplaysOnDetail(): void
    {
        $phoneNumber = '+34-91-123-4567';
        $entity = $this->createFieldTestEntity([
            'telephoneField' => $phoneNumber,
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $telephoneFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'TelephoneField') || false !== stripos($label, 'Telephone Field') || false !== stripos($label, 'Telephone')) {
                $telephoneFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString($phoneNumber, $fieldValue);
                break;
            }
        }

        static::assertTrue($telephoneFieldFound, 'TelephoneField should be displayed on detail page');
    }

    public function testTelephoneFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        $telephoneFieldInput = $crawler->filter('#FieldTestEntity_telephoneField');
        static::assertCount(1, $telephoneFieldInput, 'TelephoneField input should exist in form');
        static::assertSame('tel', $telephoneFieldInput->attr('type'), 'TelephoneField should be a tel input');
    }

    public function testTelephoneFieldSubmission(): void
    {
        $phoneNumber = '+1-800-555-1234';
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[telephoneField]'] = $phoneNumber;
        $form['FieldTestEntity[slugField]'] = 'tel-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['telephoneField' => $phoneNumber]);
        static::assertNotNull($entity, 'Entity should be created with the submitted telephone value');
        static::assertSame($phoneNumber, $entity->getTelephoneField());
    }

    public function testTelephoneFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'telephoneField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $telephoneFieldCell = $entityRow->filter('td[data-column="telephoneField"]');
        static::assertCount(1, $telephoneFieldCell, 'Telephone field cell should exist even with null value');
    }
}
