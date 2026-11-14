<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;

/**
 * Used by PlaceholderEscapingTest to check the page titles and action labels that
 * use the entity placeholders. They are rendered as HTML, so the developer HTML
 * must be kept and the entity values must be escaped.
 *
 * @extends AbstractCrudController<Category>
 */
class PlaceholderTitleCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_DETAIL, 'Category #%entity_id% <b>%entity_as_string%</b>')
            ->setPageTitle(Crud::PAGE_EDIT, static fn (Category $category): string => sprintf('Editing <b>%s</b>', htmlspecialchars((string) $category->getName())));
    }

    public function configureActions(Actions $actions): Actions
    {
        $archive = Action::new('archive', static fn (Category $category): string => sprintf('Archive <b>%s</b>', htmlspecialchars((string) $category->getName())))
            ->linkToCrudAction(Action::DETAIL)
            ->askConfirmation('Archive %entity_as_string% (#%entity_id%)?', 'Yes, archive %entity_as_string%');

        $manage = ActionGroup::new('manage', 'Manage')
            ->addMainAction(Action::new('publish', static fn (Category $category): string => sprintf('Publish <b>%s</b>', htmlspecialchars((string) $category->getName())))->linkToCrudAction(Action::DETAIL))
            ->addAction(Action::new('hide', static fn (Category $category): string => sprintf('Hide <b>%s</b>', htmlspecialchars((string) $category->getName())))->linkToCrudAction(Action::DETAIL))
            ->addAction(Action::new('lock', 'Lock <b>#%entity_id%</b>')->linkToCrudAction(Action::DETAIL));

        return $actions
            ->add(Crud::PAGE_INDEX, $archive)
            ->add(Crud::PAGE_DETAIL, $archive)
            ->add(Crud::PAGE_DETAIL, $manage)
            ->add(Crud::PAGE_EDIT, $archive);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('slug'),
        ];
    }
}
