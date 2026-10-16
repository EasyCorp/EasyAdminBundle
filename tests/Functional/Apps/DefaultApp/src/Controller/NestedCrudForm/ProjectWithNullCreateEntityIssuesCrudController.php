<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;

/**
 * @extends AbstractCrudController<Project>
 */
class ProjectWithNullCreateEntityIssuesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Project Name');
        yield CollectionField::new('projectIssues', 'Issues')
            ->useEntryCrudForm(ProjectIssueNullCreateEntityCrudController::class);
    }
}
