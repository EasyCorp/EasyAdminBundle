<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ChoiceLabels;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostWithHtmlChoiceLabelsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield ChoiceField::new('views')->setChoices(['<b>Views 1</b>' => 1])->renderAsBadges();
        yield ChoiceField::new('slug')->setChoices(['<b>Slug 1</b>' => 'slug-1'])->renderAsBadges()->escapeHtml();
        yield ChoiceField::new('title')->setChoices(['<b>Title 1</b>' => 'title-1'])->escapeHtml();
    }
}
