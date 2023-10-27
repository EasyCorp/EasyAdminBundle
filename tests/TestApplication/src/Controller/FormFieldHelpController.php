<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;
use function Symfony\Component\Translation\t;

/**
 * Used to test the ->setHelp() method of fields and how that
 * help message is rendered in the form.
 */
class FormFieldHelpController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'), // this field doesn't define a help message on purpose
            TextField::new('title')->setHelp(''),
            TextField::new('slug')->setHelp('Lorem Ipsum 1'),
            TextField::new('content')->setHelp('<b>Lorem</b> Ipsum <em class="foo">2</em>'),
            DateTimeField::new('createdAt')->setHelp(t('Lorem Ipsum 3')),
            DateTimeField::new('publishedAt')->setHelp(t('Lorem <a href="https://example.com">Ipsum</a> <b>4</b>')),
        ];
    }
}
