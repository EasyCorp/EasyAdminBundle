<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\ImageFieldNoPreviewCrudController;

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

        // image field should be wrapped in the ea-imageupload container
        $imageUploadContainer = $crawler->filter('.ea-imageupload');
        static::assertGreaterThan(0, $imageUploadContainer->count(), 'Image upload container (.ea-imageupload) should exist');

        // the preview container should be present (for JS to populate on file selection)
        $previewContainer = $crawler->filter('[data-ea-imageupload-preview]');
        static::assertGreaterThan(0, $previewContainer->count(), 'Preview container should exist in new form');

        // a file input should exist inside the container
        $imageFieldInput = $crawler->filter('.ea-imageupload input[type="file"]');
        static::assertGreaterThan(0, $imageFieldInput->count(), 'File input should exist inside image upload container');
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

        $form = $crawler->filter('form[name="FieldTestEntity"]');
        static::assertCount(1, $form, 'Edit form should exist');

        $imageUploadContainer = $crawler->filter('.ea-imageupload');
        static::assertGreaterThan(0, $imageUploadContainer->count(), 'Image upload container should exist in edit form');

        $previewContainer = $crawler->filter('[data-ea-imageupload-preview]');
        static::assertGreaterThan(0, $previewContainer->count(), 'Preview container should exist in edit form');

        $previewImage = $previewContainer->filter('.ea-lightbox-thumbnail img');
        static::assertGreaterThan(0, $previewImage->count(), 'Preview should show existing image thumbnail');
        static::assertStringContainsString('original-image.jpg', $previewImage->attr('src'));

        $lightboxDiv = $previewContainer->filter('.ea-lightbox');
        static::assertGreaterThan(0, $lightboxDiv->count(), 'Lightbox div should exist for the preview image');
    }

    public function testImageFieldNewFormHasEmptyPreviewContainer(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        $previewContainer = $crawler->filter('[data-ea-imageupload-preview]');
        static::assertGreaterThan(0, $previewContainer->count(), 'Preview container should exist in new form');

        $previewImages = $previewContainer->filter('.ea-lightbox-thumbnail');
        static::assertCount(0, $previewImages, 'No image thumbnails should exist in new form preview');
    }

    public function testImageFieldShowPreviewFalseHidesPreview(): void
    {
        $noPreviewController = ImageFieldNoPreviewCrudController::class;

        $crawler = $this->client->request('GET', $this->generateNewFormUrl(controllerFqcn: $noPreviewController));

        $imageUploadContainer = $crawler->filter('.ea-imageupload');
        static::assertGreaterThan(0, $imageUploadContainer->count(), 'Image upload container should still exist');

        $previewContainer = $crawler->filter('[data-ea-imageupload-preview]');
        static::assertCount(0, $previewContainer, 'Preview container should not exist when showPreview(false)');
    }

    public function testImageFieldShowPreviewFalseHidesPreviewOnEdit(): void
    {
        $noPreviewController = ImageFieldNoPreviewCrudController::class;

        $entity = $this->createFieldTestEntity([
            'imageField' => 'hidden-preview.jpg',
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId(), controllerFqcn: $noPreviewController));

        $previewContainer = $crawler->filter('[data-ea-imageupload-preview]');
        static::assertCount(0, $previewContainer, 'Preview container should not exist on edit when showPreview(false)');

        // the file input should still work
        $fileInput = $crawler->filter('.ea-imageupload input[type="file"]');
        static::assertGreaterThan(0, $fileInput->count(), 'File input should still exist when preview is disabled');
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
