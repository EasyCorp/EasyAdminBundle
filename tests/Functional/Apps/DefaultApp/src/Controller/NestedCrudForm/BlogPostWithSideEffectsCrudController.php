<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\NestedCrudForm;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;

/**
 * Simulates an entity factory/constructor that keeps a bidirectional association in sync.
 */
class BlogPostWithSideEffectsCrudController extends BlogPostNestedCrudController
{
    public function createEntity(string $entityFqcn): object
    {
        $post = parent::createEntity($entityFqcn);
        $parent = $this->getContext()?->getEntity()->getInstance();

        if ($parent instanceof User) {
            $parent->addBlogPost($post);
        }

        return $post;
    }
}
