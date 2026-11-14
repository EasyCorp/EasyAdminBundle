<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * Used by PlaceholderEscapingTest to check the default page titles when the
 * entity label is built by a closure that escapes the entity values.
 *
 * @extends AbstractCrudController<Category>
 */
class PlaceholderEntityLabelCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityLabelInSingular(
            static fn (?Category $category): string => null === $category ? 'Category' : sprintf('Category <i>%s</i>', htmlspecialchars((string) $category->getName()))
        );
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('slug'),
        ];
    }
}
