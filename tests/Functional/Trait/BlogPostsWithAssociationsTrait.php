<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;

/**
 * Creates the blog posts used to test how the associations of index pages are loaded:
 * 20 blog posts written by 5 users, where only the first 10 have a publisher (another
 * of those users) and each one belongs to one of 10 categories.
 */
trait BlogPostsWithAssociationsTrait
{
    /** @var list<User> */
    private array $users = [];
    /** @var list<Category> */
    private array $categories = [];
    /** @var list<BlogPost> */
    private array $blogPosts = [];
    /** @var list<BlogPost> */
    private array $searchedBlogPosts = [];

    private function createBlogPostsWithAssociations(): void
    {
        $this->users = UserFactory::createSequence(array_map(static fn (int $i): array => ['name' => 'User '.$i], range(0, 4)));
        $this->categories = CategoryFactory::createSequence(array_map(static fn (int $i): array => ['name' => 'Category '.$i], range(0, 9)));

        $this->blogPosts = [];
        foreach (range(0, 19) as $i) {
            $this->blogPosts[] = BlogPostFactory::createOne([
                // some blog posts share a word, which is used to search them
                'title' => ($i < 12 ? 'Searched' : 'Other').' Blog Post '.$i,
                'author' => $this->users[$i % 5],
                'publisher' => $i < 10 ? $this->users[($i + 1) % 5] : null,
                'categories' => [$this->categories[$i % 10]],
            ]);
        }

        $this->searchedBlogPosts = \array_slice($this->blogPosts, 0, 12);
    }
}
