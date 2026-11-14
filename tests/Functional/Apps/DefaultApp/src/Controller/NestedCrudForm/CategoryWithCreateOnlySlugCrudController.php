<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * CRUD controller for Category whose slug can only be set when creating the category and
 * whose description can only be set when editing it. Its custom pages show other fields.
 * This is used to test which page (NEW or EDIT) is used by CRUD forms embedded in other nested CRUD forms.
 *
 * @extends AbstractCrudController<Category>
 */
class CategoryWithCreateOnlySlugCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        if ('custom_new' === $pageName) {
            yield TextField::new('name');
            yield TextField::new('slug');

            return;
        }

        if ('custom_edit' === $pageName) {
            yield TextField::new('name');
            yield IntegerField::new('priority');

            return;
        }

        yield TextField::new('name');
        yield TextField::new('slug')->onlyWhenCreating();
        yield TextField::new('description')->onlyWhenUpdating();
    }
}
