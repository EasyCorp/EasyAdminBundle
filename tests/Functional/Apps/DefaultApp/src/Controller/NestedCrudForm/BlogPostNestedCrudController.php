<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;

/**
 * CRUD controller for BlogPost that accesses $context->getEntity() in configureFields().
 * This is used to test that nested CRUD forms receive the correct entity in the context.
 *
 * @extends AbstractCrudController<BlogPost>
 */
class BlogPostNestedCrudController extends AbstractCrudController
{
    public const DEFAULT_ENTRY_TITLE = 'New blog post (from createEntity)';

    public static int $createEntityCallCount = 0;

    public static function getEntityFqcn(): string
    {
        return BlogPost::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Blog Post')
            ->setEntityLabelInPlural('Blog Posts');
    }

    public function configureFields(string $pageName): iterable
    {
        // in nested CRUD forms the context used to return the parent entity (User)
        // instead of BlogPost, which broke calls to BlogPost-specific methods
        $context = $this->getContext();
        if (null !== $context) {
            $entity = $context->getEntity();
            $entityFqcn = $entity->getFqcn();

            if (BlogPost::class !== $entityFqcn) {
                throw new \LogicException(sprintf(
                    'Expected entity FQCN to be "%s", but got "%s". The AdminContext is not being swapped correctly for nested CRUD forms.',
                    BlogPost::class,
                    $entityFqcn
                ));
            }
        }

        yield TextField::new('title', 'Blog Post Title');
    }

    public function createEntity(string $entityFqcn): object
    {
        // overriding createEntity() must be honored when this controller is embedded
        // via CollectionField::useEntryCrudForm() (see #6991). The static counter lets
        // tests verify that both `prototype_data` (build) and `entry_options.empty_data`
        // (submit) reach this method.
        ++self::$createEntityCallCount;

        // the form only shows the title, so the other required properties get defaults here
        return (new BlogPost())
            ->setTitle(self::DEFAULT_ENTRY_TITLE)
            ->setSlug('new-blog-post')
            ->setContent('New blog post content')
            ->setCreatedAt(new \DateTimeImmutable());
    }
}
