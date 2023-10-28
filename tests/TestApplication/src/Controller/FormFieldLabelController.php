<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;
use function Symfony\Component\Translation\t;

/**
 * Used to test the different types of labels that fields can configure.
 */
class FormFieldLabelController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // the custom CSS classes are needed so we can select the fields in tests
            IdField::new('id')->addCssClass('field-id'),
            TextField::new('title', null)->addCssClass('field-title'),
            TextField::new('slug', false)->addCssClass('field-slug'),
            TextField::new('content', '')->addCssClass('field-content'),
            DateTimeField::new('createdAt', 'Lorem Ipsum 1')->addCssClass('field-created-at'),
            DateTimeField::new('publishedAt', t('Lorem Ipsum 2'))->addCssClass('field-published-at'),
            AssociationField::new('author', 'Lorem <a href="https://example.com">Ipsum</a> <b>3</b>')->addCssClass('field-author'),
            AssociationField::new('publisher', t('Lorem <a href="https://example.com">Ipsum</a> <b>4</b>'))->addCssClass('field-publisher'),
        ];
    }
}
