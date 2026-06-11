<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;

class ImageFieldTest extends AbstractFieldFunctionalTest
{
    public function testImageFieldDisplaysOnIndex(): void
    {
        $entity = $this->createFieldTestEntity([
            'imageField' => 'test-image.jpg',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        $imageFieldCell = $entityRow->filter('td[data-column="imageField"]');
        static::assertCount(1, $imageFieldCell, 'Image field cell should exist');

        // image field renders as an img tag on index
        $image = $imageFieldCell->filter('img');
        if ($image->count() > 0) {
            static::assertStringContainsString('test-image.jpg', $image->attr('src'));
        }
    }

    public function testImageFieldDisplaysOnDetail(): void
    {
        $entity = $this->createFieldTestEntity([
            'imageField' => 'detail-image.png',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        // image field should be rendered on the detail page
        $html = $crawler->html();
        static::assertStringContainsString('detail-image.png', $html);
    }

    public function testImageFieldInForm(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Form should exist');

        // image field may be rendered as a file input or as a container with file input
        // check that the image field container or input exists
        $imageFieldContainer = $crawler->filter('.field-image');
        $imageFieldInput = $crawler->filter('input[type="file"][name*="imageField"]');

        static::assertTrue(
            $imageFieldContainer->count() > 0 || $imageFieldInput->count() > 0,
            'Image field should exist in form'
        );
    }

    public function testImageFieldWithNullValue(): void
    {
        $entity = $this->createFieldTestEntity([
            'imageField' => null,
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $entityRow = $crawler->filter(sprintf('tr[data-id="%d"]', $entity->getId()));
        static::assertCount(1, $entityRow, 'Entity row should exist');

        // null image should render without errors
        $imageFieldCell = $entityRow->filter('td[data-column="imageField"]');
        static::assertCount(1, $imageFieldCell, 'Image field cell should exist even with null value');
    }

    public function testImageFieldEdit(): void
    {
        $entity = $this->createFieldTestEntity([
            'imageField' => 'original-image.jpg',
            'slugField' => 'image-edit-test',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        // the edit form should load successfully and contain an image field
        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Edit form should exist');

        // check that image field exists in form
        $imageFieldContainer = $crawler->filter('.field-image');
        $imageFieldInput = $crawler->filter('input[type="file"][name*="imageField"]');

        static::assertTrue(
            $imageFieldContainer->count() > 0 || $imageFieldInput->count() > 0,
            'Image field should exist in edit form'
        );
    }

    public function testImageFieldWithDifferentExtensions(): void
    {
        $extensions = ['jpg', 'png', 'gif', 'webp'];

        foreach ($extensions as $extension) {
            $entity = $this->createFieldTestEntity([
                'imageField' => sprintf('test-image.%s', $extension),
                'slugField' => sprintf('extension-test-%s', $extension),
            ]);

            $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));
            $html = $crawler->html();

            static::assertStringContainsString(
                sprintf('test-image.%s', $extension),
                $html,
                sprintf('Image with %s extension should be displayed', $extension)
            );
        }
    }

    public function testImageFieldWithPath(): void
    {
        $entity = $this->createFieldTestEntity([
            'imageField' => 'subdir/nested/image.jpg',
        ]);

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        $html = $crawler->html();
        static::assertStringContainsString('subdir/nested/image.jpg', $html);
    }
}
