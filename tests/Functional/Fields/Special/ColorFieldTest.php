<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Special;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

class ColorFieldTest extends AbstractFieldFunctionalTest
{
    public function testColorFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'colorField' => '#ff5733',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $colorFieldCell = $entityRow->filter('td[data-column="colorField"]');
        // color field should display a color swatch or the hex value
        $cellHtml = $colorFieldCell->html();
        static::assertTrue(
            str_contains($cellHtml, '#ff5733') || str_contains($cellHtml, 'ff5733') || str_contains($cellHtml, 'background'),
            sprintf('ColorField should display color value or swatch, got: %s', $cellHtml)
        );
    }

    public function testColorFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'colorField' => '#00ff00',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $fieldGroups = $crawler->filter('.content-body .field-group');
        $colorFieldFound = false;

        foreach ($fieldGroups as $fieldGroup) {
            $groupCrawler = new Crawler($fieldGroup);
            $label = $groupCrawler->filter('.field-label')->text();

            if (false !== stripos($label, 'ColorField') || false !== stripos($label, 'Color Field') || false !== stripos($label, 'Color')) {
                $colorFieldFound = true;
                $fieldValue = $groupCrawler->filter('.field-value');
                $valueHtml = $fieldValue->html();
                static::assertTrue(
                    str_contains($valueHtml, '#00ff00') || str_contains($valueHtml, '00ff00') || str_contains($valueHtml, 'background'),
                    sprintf('ColorField should display color value on detail, got: %s', $valueHtml)
                );
                break;
            }
        }

        static::assertTrue($colorFieldFound, 'ColorField should be displayed on detail page');
    }

    public function testColorFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // colorField renders as a color input
        $colorFieldInput = $crawler->filter('#FieldTestEntity_colorField');
        static::assertCount(1, $colorFieldInput, 'ColorField input should exist in form');
        static::assertSame('color', $colorFieldInput->attr('type'), 'ColorField should be a color input');
    }

    public function testColorFieldSubmission(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[colorField]'] = '#0000ff';
        $form['FieldTestEntity[slugField]'] = 'color-test';

        $this->client->submit($form);

        $entity = $this->fieldTestEntities->findOneBy(['colorField' => '#0000ff']);
        static::assertNotNull($entity, 'Entity should be created with the submitted color');
        static::assertSame('#0000ff', $entity->getColorField());
    }

    public function testColorFieldEdit(): void
    {
        $originalColor = '#ffffff';
        $entity = $this->createFieldTestEntity([
            'colorField' => $originalColor,
            'slugField' => 'color-edit',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        static::assertSame($originalColor, $form['FieldTestEntity[colorField]']->getValue());

        $form['FieldTestEntity[colorField]'] = '#000000';
        $this->client->submit($form);

        $this->entityManager->clear();
        $updatedEntity = $this->fieldTestEntities->find($entity->getId());
        static::assertSame('#000000', $updatedEntity->getColorField());
    }

    public function testColorFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'colorField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $colorFieldCell = $entityRow->filter('td[data-column="colorField"]');
        static::assertCount(1, $colorFieldCell, 'Color field cell should exist even with null value');
    }

    public function testColorFieldWithCommonColors(): void
    {
        $colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff'];

        foreach ($colors as $color) {
            $entity = $this->createFieldTestEntity([
                'colorField' => $color,
                'slugField' => 'color-'.substr($color, 1),
            ]);

            static::assertSame($color, $entity->getColorField());
        }
    }
}
