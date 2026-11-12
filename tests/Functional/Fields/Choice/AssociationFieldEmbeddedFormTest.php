<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\EmbeddedForm\BlogPostWithEmbeddedUsersCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;

/**
 * Tests which CRUD controller provides the form embedded by
 * AssociationField::renderAsEmbeddedForm().
 */
class AssociationFieldEmbeddedFormTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return BlogPostWithEmbeddedUsersCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testEmbeddedFormUsesTheControllerSetWithSetCrudController(): void
    {
        $blogPost = BlogPostFactory::createOne([
            'author' => UserFactory::createOne(['name' => 'User 1', 'email' => 'user1@example.com']),
            'publisher' => UserFactory::createOne(['name' => 'User 2', 'email' => 'user2@example.com']),
        ]);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($blogPost->getId()));

        static::assertResponseIsSuccessful();

        // the author field embeds the form of UserNameEmbeddedCrudController
        static::assertCount(1, $crawler->filter('input[name="BlogPost[author][name]"][value="User 1"]'));
        static::assertCount(0, $crawler->filter('input[name="BlogPost[author][email]"]'));

        // the publisher field embeds the form of UserEmailEmbeddedCrudController
        static::assertCount(1, $crawler->filter('input[name="BlogPost[publisher][email]"][value="user2@example.com"]'));
        static::assertCount(0, $crawler->filter('input[name="BlogPost[publisher][name]"]'));
    }
}
