<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\TextTemplate;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * Fields rendered with the 'crud/field/text', 'crud/field/textarea' and 'crud/field/choice'
 * templates that are not processed by the configurators of TextField, TextareaField and ChoiceField.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class NonTextFieldsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            'id',
            'title',
            'author.name',
            'displayTitle',
            Field::new('author.favoriteCategory'),
            SlugField::new('slug')->setTargetFieldName('title'),
            EmailField::new('author.email')->setTemplateName('crud/field/text'),
            Field::new('author.address.city')->setTemplateName('crud/field/textarea'),
            Field::new('author.favoriteCategory.name')->setTemplateName('crud/field/choice'),
        ];
    }
}
