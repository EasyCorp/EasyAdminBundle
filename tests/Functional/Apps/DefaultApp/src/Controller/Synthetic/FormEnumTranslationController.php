<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\BlogPost;

/**
 * @extends AbstractCrudController<BlogPost>
 */
class FormEnumTranslationController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // Test translating enums in forms. When enums implement TranslatableInterface, this should be done by Symfony
        // automagically.
        return [
            ChoiceField::new('state', 'State'),
        ];
    }
}
