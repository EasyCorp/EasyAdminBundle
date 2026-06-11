<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectReleaseCategory;

/**
 * @extends AbstractCrudController<ProjectReleaseCategory>
 */
class ProjectReleaseCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProjectReleaseCategory::class;
    }
}
