<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\UrlSortSecurityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;

/**
 * End-to-end checks that the URL-derived ?sort[...] parameter cannot order the
 * index by a Doctrine-mapped property that the controller hasn't exposed, and
 * cannot smuggle extra ORDER BY columns through either the key or the value.
 *
 * The rows are created so that sorting by `id`, `title` or `slug` each
 * produces a distinct row order. That lets every test compare against the controller's default `id ASC` order and detect when an
 * attacker-controlled sort actually took effect.
 */
class UrlSortSecurityTest extends AbstractCrudTestCase
{
    // title values in id ASC order (the controller's default sort)
    private const DEFAULT_ORDER = ['Zulu', 'Alpha', 'Mike'];

    protected function getControllerFqcn(): string
    {
        return UrlSortSecurityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        BlogPostFactory::createSequence([
            ['title' => 'Zulu', 'slug' => 'Alpha'],
            ['title' => 'Alpha', 'slug' => 'Zulu'],
            ['title' => 'Mike', 'slug' => 'Mike'],
        ]);
    }

    public function testValidCustomSortByExposedFieldIsApplied(): void
    {
        // sanity: ?sort[title]=ASC is legitimate and reorders the index
        $this->client->request('GET', $this->generateIndexUrl().'?sort%5Btitle%5D=ASC');

        $this->assertResponseIsSuccessful();
        $this->assertRowsInOrder(['Alpha', 'Mike', 'Zulu']);
    }

    public function testCustomSortByHiddenMappedFieldFallsBackToDefault(): void
    {
        // ?sort[slug]=ASC: `slug` is mapped on the entity but not
        // exposed via configureFields(), so the gate must reject it and the
        // controller's `setDefaultSort(['id' => 'ASC'])` must remain in effect
        $this->client->request('GET', $this->generateIndexUrl().'?sort%5Bslug%5D=ASC');

        $this->assertResponseIsSuccessful();
        $this->assertRowsInOrder(self::DEFAULT_ORDER);
    }

    public function testCustomSortValueSmugglingIsRejected(): void
    {
        // ?sort[title]=ASC, entity.slug DESC
        // Expr\OrderBy::add() concatenates "$property $direction", so without a
        // direction-value check this smuggles a second OrderByItem into the DQL
        $url = $this->generateIndexUrl().'?'.http_build_query([
            'sort' => ['title' => 'ASC, entity.slug DESC'],
        ]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertRowsInOrder(self::DEFAULT_ORDER);
    }

    public function testCustomSortKeyWithCommaIsRejected(): void
    {
        // ?sort[title,entity.slug]=ASC — comma in the key would
        // otherwise expand into two ORDER BY columns
        $url = $this->generateIndexUrl().'?'.http_build_query([
            'sort' => ['title,entity.slug' => 'ASC'],
        ]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertRowsInOrder(self::DEFAULT_ORDER);
    }

    /**
     * @param list<string> $expectedTitles
     */
    private function assertRowsInOrder(array $expectedTitles): void
    {
        $this->assertSelectorCount(\count($expectedTitles), 'tbody tr');

        foreach ($expectedTitles as $i => $value) {
            $this->assertSelectorTextSame(
                sprintf('tbody tr:nth-child(%d) td[data-column="title"]', $i + 1),
                $value,
                sprintf('Expected "%s" in row %d', $value, $i + 1),
            );
        }
    }
}
