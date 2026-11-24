<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Media;

use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;

/**
 * VichUploaderBundle is not installed in the test apps, so its widgets are
 * rendered directly with the variables that its form types pass to them.
 *
 * Links to uploaded files force a download instead of opening the file, except
 * for the files of well-known types that browsers can't run scripts from.
 */
class VichFormThemeTest extends KernelTestCase
{
    #[TestWith(['/uploads/payload.svg', true])]
    #[TestWith(['/uploads/payload.SVG', true])]
    #[TestWith(['/uploads/photo.png', false])]
    #[TestWith(['/uploads/photo.png?v=2', false])]
    #[TestWith(['/uploads/payload.svg?.png', true])]
    #[TestWith(['/uploads/resource-map.rdf', true])]
    #[TestWith(['/download/42', true])]
    public function testImageWidgetThumbnailLink(string $downloadUri, bool $forcesDownload): void
    {
        $crawler = $this->renderVichBlock('vich_image_widget', [
            'image_uri' => '/uploads/thumbnail.png',
            'download_uri' => $downloadUri,
        ]);

        $link = $crawler->filter('a.ea-lightbox-thumbnail');
        static::assertCount(1, $link);
        static::assertSame($downloadUri, $link->attr('href'));
        static::assertSame($forcesDownload, null !== $link->attr('download'));
        static::assertSame($forcesDownload ? null : '_blank', $link->attr('target'));
        static::assertCount(1, $crawler->filter($link->attr('data-ea-lightbox-content-selector').' img'));
    }

    #[TestWith(['/uploads/payload.svg', true])]
    #[TestWith(['/uploads/report.pdf', false])]
    #[TestWith(['/uploads/payload.svg#.pdf', true])]
    #[TestWith(['/uploads/build-notes.mk', true])]
    #[TestWith(['/download/42', true])]
    public function testFileWidgetLink(string $downloadUri, bool $forcesDownload): void
    {
        $crawler = $this->renderVichBlock('vich_file_widget', ['download_uri' => $downloadUri]);

        $link = $crawler->filter('a.ea-vich-file-name');
        static::assertCount(1, $link);
        static::assertSame($forcesDownload, null !== $link->attr('download'));
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function renderVichBlock(string $blockName, array $variables): Crawler
    {
        /** @var FormFactoryInterface $formFactory */
        $formFactory = static::getContainer()->get('form.factory');
        $form = $formFactory->createNamedBuilder('upload', FormType::class, null, ['csrf_protection' => false])
            ->add('file', FileType::class)
            ->getForm()
            ->createView();

        /** @var Environment $twig */
        $twig = static::getContainer()->get('twig');
        $html = $twig->load('@EasyAdmin/crud/form_theme.html.twig')->renderBlock($blockName, $variables + [
            'form' => $form,
            'id' => 'upload',
            'asset_helper' => false,
            'translation_domain' => false,
        ]);

        return new Crawler($html);
    }
}
