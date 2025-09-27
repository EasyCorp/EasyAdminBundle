<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

/**
 * Tests all the different ways of configuring asn customizing the assets.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class AssetsController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return ['title', 'content', 'publishedAt'];
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('https://cdn.example.com/css1.css')
            ->addCssFile('css2.css')
            ->addCssFile('/css3.css')
            ->addCssFile('/foo/bar/css4.css')
            ->addCssFile(Asset::new('css5.css'))
            ->addCssFile(Asset::new('css6.css')->preload())
            ->addCssFile(Asset::new('css7.css')->preload()->nopush())
            ->addCssFile(Asset::new('css8.css')->htmlAttr('media', 'print'))
            ->addCssFile(Asset::new('css9.css')->htmlAttrs(['media' => 'print', 'title' => 'foo']))
            ->addCssFile(Asset::new('css10.css')->webpackPackageName('foo'))
            ->addCssFile(Asset::new('css11.css')->webpackEntrypointName('foo'))
            ->addCssFile(Asset::new('css12.css')->webpackEntrypointName('foo')->webpackPackageName('bar'))

            ->addJsFile('https://cdn.example.com/js1.js')
            ->addJsFile('js2.js')
            ->addJsFile('/js3.js')
            ->addJsFile('/foo/bar/js4.js')
            ->addJsFile(Asset::new('js5.js'))
            ->addJsFile(Asset::new('js6.js')->preload())
            ->addJsFile(Asset::new('js7.js')->preload()->nopush())
            ->addJsFile(Asset::new('js8.js')->defer())
            ->addJsFile(Asset::new('js9.js')->async())
            ->addJsFile(Asset::new('js10.js')->defer()->async())
            ->addJsFile(Asset::new('js11.js')->preload()->nopush()->defer()->async())
            ->addJsFile(Asset::new('js12.js')->htmlAttr('foo', 'bar'))
            ->addJsFile(Asset::new('js13.js')->htmlAttrs(['foo' => 'bar', 'baz' => 'qux']))

            ->addHtmlContentToHead('<link rel="stylesheet" href="https://cdn.example.com/css11.css">')
            ->addHtmlContentToBody('<script src="https://cdn.example.com/js14.js"></script>')
        ;
    }
}
