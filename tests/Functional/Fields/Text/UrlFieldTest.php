<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class UrlFieldTest extends AbstractFieldFunctionalTest
{
    public function testUrlFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'urlField' => 'https://example.com',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $urlFieldCell = $entityRow->filter('td[data-column="urlField"]');
        static::assertStringContainsString('example.com', $urlFieldCell->text());
    }

    public function testUrlFieldDisplaysAsLinkOnIndex(): void
    {
        $url = 'https://www.symfony.com/doc';
        $entity = $this->createFieldTestEntity([
            'urlField' => $url,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $urlLink = $entityRow->filter('td[data-column="urlField"] a');
        static::assertGreaterThan(0, $urlLink->count(), 'URL field should render as a link');
        static::assertSame($url, $urlLink->attr('href'));
        // the displayed text should be "pretty" (without https://, www., etc.)
        static::assertSame('symfony.com/doc', trim($urlLink->text()));
    }

    public function testUrlFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'urlField' => 'https://detailed.example.org/path',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $urlFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'UrlField') || false !== stripos($label, 'Url Field') || false !== stripos($label, 'URL')) {
                $urlFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                static::assertStringContainsString('detailed.example.org', $fieldValue->text());
                break;
            }
        }

        static::assertTrue($urlFieldFound, 'UrlField should be displayed on detail page');
    }

    public function testUrlFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        $urlFieldInput = $crawler->filter('#FieldTestEntity_urlField');
        static::assertCount(1, $urlFieldInput, 'UrlField input should exist in form');
        static::assertSame('url', $urlFieldInput->attr('type'), 'UrlField should be a url input');
    }

    public function testUrlFieldSubmission(): void
    {
        $url = 'https://submitted.example.net';
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[urlField]'] = $url;
        $form['FieldTestEntity[slugField]'] = 'url-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['urlField' => $url]);
        static::assertNotNull($entity, 'Entity should be created with the submitted URL value');
        static::assertSame($url, $entity->getUrlField());
    }

    public function testUrlFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'urlField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $urlFieldCell = $entityRow->filter('td[data-column="urlField"]');
        static::assertCount(1, $urlFieldCell, 'URL field cell should exist even with null value');
    }
}
