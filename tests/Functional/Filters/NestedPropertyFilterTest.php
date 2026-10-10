<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;

/**
 * Anchors the first rule of the "Filters" section of skills/easyadmin/SKILL.md:
 * filters added by property name guess their class for nested paths too.
 * If this behavior changes, update that skill rule too.
 *
 * Fixture data: 20 blog posts whose authors are "User 0" to "User 4" (post N is written by "User N % 5").
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
        $this->client->request('GET', $this->generateFilteredIndexUrl([
            'author:name' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'User 1',
            ],
        ]));

        $this->assertFilteredCount(4);
        $titleCells = $this->client->getCrawler()->filter('td[data-column="title"]');
        $this->assertCount(4, $titleCells);
        foreach ($titleCells as $cell) {
            $postNumber = (int) str_replace('Blog Post ', '', trim($cell->textContent));
            $this->assertSame(1, $postNumber % 5);
        }
    }
}
