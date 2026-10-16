<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectIssue;

/**
 * Embedded CRUD controller whose createEntity() returns null, so the collection
 * entries are created by the "empty_data" option of the entry form.
 *
 * @extends AbstractCrudController<ProjectIssue>
 */
class ProjectIssueNullCreateEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectIssue::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Issue Name');
    }

    public function createEntity(string $entityFqcn): ?object
    {
        return null;
    }
}
