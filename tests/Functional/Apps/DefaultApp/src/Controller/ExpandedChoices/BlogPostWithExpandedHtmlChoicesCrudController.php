<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ExpandedChoices;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithExpandedHtmlChoicesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title');
        yield AssociationField::new('categories')->setFormTypeOptions(['expanded' => true, 'choice_label' => 'name'])->renderAsHtml();
        yield AssociationField::new('author')->setFormTypeOption('expanded', true)->renderAsHtml();
        yield ChoiceField::new('slug')->setChoices(['<b>Choice 1</b>' => 'choice-1'])->renderExpanded()->escapeHtml(false);
    }
}
