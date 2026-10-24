<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;

/**
 * Anchors the first rule of the "Filters" section of skills/easyadmin/SKILL.md:
 * filters added by property name guess their class for nested paths too.
 * If this behavior changes, update that skill rule too.
 */
class NestedPropertyFilterTest extends FilterFunctionalTestCase
{
    protected function getControllerFqcn(): string
    {
        return BlogPostCrudController::class;
    }

    public function testGuessedFilterTypeOfNestedProperty(): void
    {
        $crawler = $this->client->request('GET', $this->getCrudUrl('renderFilters'));

        $this->assertCount(1, $crawler->filter('select[name="filters[author:name][comparison]"] option[value="like"]'));
        $this->assertCount(1, $crawler->filter('input[type="text"][name="filters[author:name][value]"]'));
    }

    public function testFilterByNestedProperty(): void
    {
        $aliceWriter = UserFactory::createOne(['name' => 'Alice Writer']);
        $aliceReviewer = UserFactory::createOne(['name' => 'Alice Reviewer']);
        $bobEditor = UserFactory::createOne(['name' => 'Bob Editor']);

        $expectedIds = [
            BlogPostFactory::createOne(['author' => $aliceWriter])->getId(),
            BlogPostFactory::createOne(['author' => $aliceWriter])->getId(),
            BlogPostFactory::createOne(['author' => $aliceReviewer])->getId(),
        ];
        BlogPostFactory::createOne(['author' => $bobEditor]);

        $this->client->request('GET', $this->generateFilteredIndexUrl([
            'author:name' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'Alice',
            ],
        ]));

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds($expectedIds);
    }
}
