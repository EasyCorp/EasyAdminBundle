<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;

/**
 * CRUD controller for Project that uses CollectionField with useEntryCrudForm().
 * This is used to test that nested CRUD forms work correctly with the AdminContext fix.
 *
 * @extends AbstractCrudController<Project>
 */
class ProjectWithNestedIssuesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Project')
            ->setEntityLabelInPlural('Projects');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Project Name');

        // use useEntryCrudForm() to render nested ProjectIssue entities using the
        // ProjectIssueNestedCrudController. This controller accesses $context->getEntity()
        // in its configureFields(), which tests the bug fix
        yield CollectionField::new('projectIssues', 'Issues')
            ->useEntryCrudForm(ProjectIssueNestedCrudController::class);
    }
}
