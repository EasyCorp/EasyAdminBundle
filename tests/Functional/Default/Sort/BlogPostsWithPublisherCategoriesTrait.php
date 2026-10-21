<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;

/**
 * Creates these blog posts, in this order, with the favorite category of their
 * publisher: "Post Alpha" (Category A), "Post Beta" (Category B), "Post Gamma"
 * (Category A) and "Post Delta" (no publisher). Categories are created in that
 * same order, so their ids increase while their names don't.
 */
trait BlogPostsWithPublisherCategoriesTrait
{
    /**
     * @var array<string, string|null>
     */
    private const PUBLISHER_CATEGORY_BY_BLOG_POST_TITLE = [
        'Post Alpha' => 'Category A',
        'Post Beta' => 'Category B',
        'Post Gamma' => 'Category A',
        'Post Delta' => null,
    ];

    private function createBlogPostsWithPublisherCategories(): void
    {
        foreach (self::PUBLISHER_CATEGORY_BY_BLOG_POST_TITLE as $title => $categoryName) {
            $publisher = null === $categoryName ? null : UserFactory::createOne([
                'favoriteCategory' => CategoryFactory::createOne(['name' => $categoryName]),
            ]);

            BlogPostFactory::createOne(['title' => $title, 'publisher' => $publisher]);
        }
    }
}
