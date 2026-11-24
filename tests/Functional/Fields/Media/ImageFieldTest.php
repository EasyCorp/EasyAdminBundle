<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTestCase;
use PHPUnit\Framework\Attributes\TestWith;

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

    public function testImageFieldHasNoHeightByDefault(): void
    {
        $entity = $this->createFieldTestEntity([
            'imageField' => 'test-image.jpg',
        ]);

        $crawler = $this->client->request('GET', $this->generateIndexUrlSortedByIdDesc());

        $thumbnail = $crawler->filter(sprintf('tr[data-id="%d"] td[data-column="imageField"] .ea-lightbox-thumbnail', $entity->getId()));
        static::assertCount(1, $thumbnail);
        static::assertNull($thumbnail->attr('style'));
    }

    public function testImageFieldWithHeight(): void
    {
        $entity = $this->createFieldTestEntity([
            'imageField' => 'test-image.jpg',
        ]);

        $crawler = $this->client->request('GET', $this->getCrudUrl('index', null, ['sort' => ['id' => 'DESC'], 'imageHeight' => 80]));

        $thumbnail = $crawler->filter(sprintf('tr[data-id="%d"] td[data-column="imageField"] .ea-lightbox-thumbnail', $entity->getId()));
        static::assertCount(1, $thumbnail);
        static::assertSame('--ea-image-field-height: 80px', $thumbnail->attr('style'));

        $crawler = $this->client->request('GET', $this->getCrudUrl('detail', $entity->getId(), ['imageHeight' => 80]));

        static::assertSame('--ea-image-field-height: 80px', $crawler->filter('.field-image .ea-lightbox-thumbnail')->attr('style'));
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

    /**
     * The thumbnail of the index and detail pages is also a link to the image,
     * which the browser follows on a Ctrl/Cmd/Shift click or a middle click. For
     * files the browser would render inline and could execute scripts from (e.g.
     * ".svg") that link must force a download instead of opening the file, while
     * the lightbox keeps showing the image through an <img> tag.
     */
    #[TestWith(['index'])]
    #[TestWith(['detail'])]
    public function testRiskyImageTypesAreNotLinkedInlineOnIndexAndDetail(string $pageName): void
    {
        $entity = $this->createFieldTestEntity(['imageField' => 'payload.svg']);
        $crawler = $this->client->request('GET', $this->getPageUrl($pageName, $entity->getId()));

        $link = $crawler->filter('a.ea-lightbox-thumbnail[href$="payload.svg"]');
        static::assertCount(1, $link);
        static::assertSame('payload.svg', $link->attr('download'));
        static::assertNull($link->attr('target'));
        static::assertCount(1, $crawler->filter($link->attr('data-ea-lightbox-content-selector').' img[src$="payload.svg"]'));
    }

    #[TestWith(['index'])]
    #[TestWith(['detail'])]
    public function testSafeImageTypesAreLinkedInlineOnIndexAndDetail(string $pageName): void
    {
        $entity = $this->createFieldTestEntity(['imageField' => 'photo.jpg']);
        $crawler = $this->client->request('GET', $this->getPageUrl($pageName, $entity->getId()));

        $link = $crawler->filter('a.ea-lightbox-thumbnail[href$="photo.jpg"]');
        static::assertCount(1, $link);
        static::assertNull($link->attr('download'));
        static::assertSame('_blank', $link->attr('target'));
        static::assertCount(1, $crawler->filter($link->attr('data-ea-lightbox-content-selector').' img[src$="photo.jpg"]'));
    }

    #[TestWith(['index'])]
    #[TestWith(['detail'])]
    public function testRiskyImageTypesAreLinkedInlineOnIndexAndDetailWhenAllowed(string $pageName): void
    {
        $entity = $this->createFieldTestEntity(['imageField' => 'payload.svg']);
        $crawler = $this->client->request('GET', $this->getPageUrl($pageName, $entity->getId(), ['allowRiskyInlineRender' => true]));

        $link = $crawler->filter('a.ea-lightbox-thumbnail[href$="payload.svg"]');
        static::assertCount(1, $link);
        static::assertNull($link->attr('download'));
        static::assertSame('_blank', $link->attr('target'));
    }

    public function testEachImageOfMultipleImageFieldIsCheckedOnDetail(): void
    {
        $entity = $this->createFieldTestEntity(['multipleImageField' => ['payload.svg', 'photo.png']]);
        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId()));

        static::assertSame('payload.svg', $crawler->filter('a.ea-lightbox-thumbnail[href$="payload.svg"]')->attr('download'));
        static::assertNull($crawler->filter('a.ea-lightbox-thumbnail[href$="photo.png"]')->attr('download'));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function getPageUrl(string $pageName, int $entityId, array $options = []): string
    {
        if ('index' === $pageName) {
            return $this->getCrudUrl('index', null, ['sort' => ['id' => 'DESC']] + $options);
        }

        return $this->getCrudUrl('detail', $entityId, $options);
    }
}
