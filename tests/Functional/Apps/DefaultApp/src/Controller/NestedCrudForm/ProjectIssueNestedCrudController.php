<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectIssue;

/**
 * CRUD controller for ProjectIssue that accesses $context->getEntity() in configureFields().
 * This is used to test that nested CRUD forms receive the correct entity in the context.
 *
 * @extends AbstractCrudController<ProjectIssue>
 */
class ProjectIssueNestedCrudController extends AbstractCrudController
{
    public const DEFAULT_ENTRY_NAME = 'New issue (from createEntity)';

    public static int $createEntityCallCount = 0;

    public static function getEntityFqcn(): string
    {
        return ProjectIssue::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Project Issue')
            ->setEntityLabelInPlural('Project Issues');
    }

    public function configureFields(string $pageName): iterable
    {
        // before the fix, this would return the parent entity (Project) instead of ProjectIssue,
        // causing errors when trying to access ProjectIssue-specific methods.
        $context = $this->getContext();
        if (null !== $context) {
            $entity = $context->getEntity();
            $entityFqcn = $entity->getFqcn();

            if (ProjectIssue::class !== $entityFqcn) {
                throw new \LogicException(sprintf(
                    'Expected entity FQCN to be "%s", but got "%s". The AdminContext is not being swapped correctly for nested CRUD forms.',
                    ProjectIssue::class,
                    $entityFqcn
                ));
            }
        }

        yield TextField::new('name', 'Issue Name');
    }

    public function createEntity(string $entityFqcn): object
    {
        // overriding createEntity() must be honored when this controller is embedded
        // via CollectionField::useEntryCrudForm() (see #6991). The static counter lets
        // tests verify that both `prototype_data` (build) and `entry_options.empty_data`
        // (submit) reach this method.
        ++self::$createEntityCallCount;

        $issue = new $entityFqcn();
        $issue->setName(self::DEFAULT_ENTRY_NAME);

        return $issue;
    }
}
