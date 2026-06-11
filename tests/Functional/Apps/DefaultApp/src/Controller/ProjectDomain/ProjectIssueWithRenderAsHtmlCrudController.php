<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectIssue;

/**
 * @extends AbstractCrudController<ProjectIssue>
 */
class ProjectIssueWithRenderAsHtmlCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectIssue::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            // test autocomplete with callback AND renderAsHtml: true
            AssociationField::new('project')->autocomplete(
                callback: static fn (Project $p): string => sprintf('<strong>%s</strong> (ID: %s)', $p->getName(), $p->getId()),
                renderAsHtml: true
            ),
        ];
    }
}
