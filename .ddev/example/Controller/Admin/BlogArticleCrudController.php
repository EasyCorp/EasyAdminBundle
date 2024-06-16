<?php

namespace App\Controller\Admin;

use App\Entity\BlogArticle;
use App\Entity\ContentBlock;
use App\Form\ContentBlockType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BlogArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogArticle::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('General');
        yield TextField::new('title')->setColumns(6);
        yield AssociationField::new('category')->setColumns(6);
        yield TextareaField::new('abstract')->setRequired(false);

        yield FormField::addTab('Contents');
        yield CollectionField::new('contents')
            ->hideOnIndex()
            ->setEntryType(ContentBlockType::class)
            ->renderExpanded(false)
            ->formatValue(function ($value, ContentBlock $entity) {
                return $entity->contents ?? '';
            })
        ;
    }
}
