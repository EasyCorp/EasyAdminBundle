<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectIssue;

/**
 * @extends AbstractCrudController<ProjectIssue>
 */
class ProjectIssueWithAutocompleteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectIssue::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // test CRUD-level autocomplete with callback
            ->autocomplete(
                callback: static fn ($entity) => sprintf('[%s] %s', $entity->getId() ?? 'NEW', (string) $entity)
            );
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            // test field-level autocomplete with callback (overrides CRUD-level)
            AssociationField::new('project')->autocomplete(
                callback: static fn (Project $p) => sprintf('Project: %s (ID: %s)', $p->getName(), $p->getId())
            ),
        ];
    }
}
