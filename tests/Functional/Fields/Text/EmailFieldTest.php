<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Text;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class EmailFieldTest extends AbstractFieldFunctionalTest
{
    public function testEmailFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'emailField' => 'test@example.com',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $emailFieldCell = $entityRow->filter('td[data-column="emailField"]');
        static::assertStringContainsString('test@example.com', $emailFieldCell->text());
    }

    public function testEmailFieldDisplaysAsLinkOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'emailField' => 'contact@example.com',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        $emailLink = $entityRow->filter('td[data-column="emailField"] a[href^="mailto:"]');
        static::assertCount(1, $emailLink, 'Email field should render as mailto link');
        static::assertSame('mailto:contact@example.com', $emailLink->attr('href'));
    }

    public function testEmailFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'emailField' => 'detailed@example.com',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $emailFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'EmailField') || false !== stripos($label, 'Email Field')) {
                $emailFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value')->text();
                static::assertStringContainsString('detailed@example.com', $fieldValue);
                break;
            }
        }

        static::assertTrue($emailFieldFound, 'EmailField should be displayed on detail page');
    }

    public function testEmailFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        $emailFieldInput = $crawler->filter('#FieldTestEntity_emailField');
        static::assertCount(1, $emailFieldInput, 'EmailField input should exist in form');
        static::assertSame('email', $emailFieldInput->attr('type'), 'EmailField should be an email input');
    }

    public function testEmailFieldSubmission(): void
    {
        $email = 'submitted@example.com';
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[emailField]'] = $email;
        $form['FieldTestEntity[slugField]'] = 'email-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['emailField' => $email]);
        static::assertNotNull($entity, 'Entity should be created with the submitted email value');
        static::assertSame($email, $entity->getEmailField());
    }

    public function testEmailFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'emailField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $emailFieldCell = $entityRow->filter('td[data-column="emailField"]');
        static::assertCount(1, $emailFieldCell, 'Email field cell should exist even with null value');
    }
}
