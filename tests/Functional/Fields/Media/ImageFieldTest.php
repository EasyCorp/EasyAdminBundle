<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;

class ImageFieldTest extends AbstractFieldFunctionalTestCase
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

    /**
     * Files the browser would render inline and could execute scripts from (e.g.
     * ".svg") must not be opened inline from the edit form: the inline "view"
     * link is suppressed and only the (safe) download link is kept. Safe image
     * types keep their inline "view" link.
     */
    public function testRiskyImageTypesAreNotViewableInlineInForm(): void
    {
        $uploadDir = self::getContainer()->getParameter('kernel.project_dir').'/public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // the form only renders a file card for files that exist on disk
        $svgName = 'functional-test-payload.svg';
        $jpgName = 'functional-test-photo.jpg';
        file_put_contents($uploadDir.$svgName, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($uploadDir.$jpgName, 'fake-jpg-bytes');

        try {
            // risky type (.svg): inline "view" link suppressed, download link kept
            $entity = $this->createFieldTestEntity(['imageField' => $svgName]);
            $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

            $card = $crawler->filter(sprintf('.ea-fileupload-card[data-filename="%s"]', $svgName));
            static::assertCount(1, $card, 'The uploaded SVG should render a file-upload card');
            static::assertCount(0, $card->filter('.ea-fileupload-action-view'), 'The inline "view" link must be suppressed for .svg files');
            static::assertCount(1, $card->filter('.ea-fileupload-action-download'), 'The "download" link must still be available for .svg files');

            // safe type (.jpg): inline "view" link kept
            $entity = $this->createFieldTestEntity(['imageField' => $jpgName]);
            $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

            $card = $crawler->filter(sprintf('.ea-fileupload-card[data-filename="%s"]', $jpgName));
            static::assertCount(1, $card, 'The uploaded JPG should render a file-upload card');
            static::assertCount(1, $card->filter('.ea-fileupload-action-view'), 'The inline "view" link must be available for safe image types');
        } finally {
            @unlink($uploadDir.$svgName);
            @unlink($uploadDir.$jpgName);
        }
    }
}
