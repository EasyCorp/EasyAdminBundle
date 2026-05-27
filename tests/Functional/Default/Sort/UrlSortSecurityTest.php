<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\UrlSortSecurityTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UrlSortSecurityTestEntity;

/**
 * End-to-end checks that the URL-derived ?sort[...] parameter cannot order the
 * index by a Doctrine-mapped property that the controller hasn't exposed, and
 * cannot smuggle extra ORDER BY columns through either the key or the value.
 *
 * The fixture is set up so that sorting by `id`, `displayedField`, `hiddenField`
 * or `otherField` each produces a distinct row order — that lets every test
 * compare against the controller's default `id ASC` order and detect when an
 * attacker-controlled sort actually took effect.
 */
class UrlSortSecurityTest extends AbstractCrudTestCase
{
    private EntityRepository $repository;

    protected function getControllerFqcn(): string
    {
        return UrlSortSecurityTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(UrlSortSecurityTestEntity::class);
    }

    public function testValidCustomSortByExposedFieldIsApplied(): void
    {
        // sanity: ?sort[displayedField]=ASC is legitimate and reorders the index
        $expected = $this->displayedFieldsInOrder('displayedField', \SORT_ASC);

        $this->client->request('GET', $this->generateIndexUrl().'?sort%5BdisplayedField%5D=ASC');

        $this->assertResponseIsSuccessful();
        $this->assertRowsInOrder($expected);
    }

    public function testCustomSortByHiddenMappedFieldFallsBackToDefault(): void
    {
        // ?sort[hiddenField]=ASC: `hiddenField` is mapped on the entity but not
        // exposed via configureFields(), so the gate must reject it and the
        // controller's `setDefaultSort(['id' => 'ASC'])` must remain in effect
        $expected = $this->displayedFieldsInOrder('id', \SORT_ASC);

        $this->client->request('GET', $this->generateIndexUrl().'?sort%5BhiddenField%5D=ASC');

        $this->assertResponseIsSuccessful();
        $this->assertRowsInOrder($expected);
    }

    public function testCustomSortValueSmugglingIsRejected(): void
    {
        // ?sort[displayedField]=ASC, entity.hiddenField DESC
        // Expr\OrderBy::add() concatenates "$property $direction", so without a
        // direction-value check this smuggles a second OrderByItem into the DQL
        $expected = $this->displayedFieldsInOrder('id', \SORT_ASC);

        $url = $this->generateIndexUrl().'?'.http_build_query([
            'sort' => ['displayedField' => 'ASC, entity.hiddenField DESC'],
        ]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertRowsInOrder($expected);
    }

    public function testCustomSortKeyWithCommaIsRejected(): void
    {
        // ?sort[displayedField,entity.hiddenField]=ASC — comma in the key would
        // otherwise expand into two ORDER BY columns
        $expected = $this->displayedFieldsInOrder('id', \SORT_ASC);

        $url = $this->generateIndexUrl().'?'.http_build_query([
            'sort' => ['displayedField,entity.hiddenField' => 'ASC'],
        ]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertRowsInOrder($expected);
    }

    /**
     * Returns the `displayedField` values of the fixture rows, ordered by the
     * given property name and direction. Used to build the expected row sequence
     * for each test independently of the DB engine's tie-breaking behavior.
     *
     * @return list<string>
     */
    private function displayedFieldsInOrder(string $property, int $direction): array
    {
        $entities = $this->repository->findAll();
        usort(
            $entities,
            static function (UrlSortSecurityTestEntity $a, UrlSortSecurityTestEntity $b) use ($property, $direction): int {
                $cmp = match ($property) {
                    'id' => $a->getId() <=> $b->getId(),
                    'displayedField' => $a->getDisplayedField() <=> $b->getDisplayedField(),
                    'hiddenField' => $a->getHiddenField() <=> $b->getHiddenField(),
                    'otherField' => $a->getOtherField() <=> $b->getOtherField(),
                };

                return \SORT_ASC === $direction ? $cmp : -$cmp;
            }
        );

        return array_map(static fn (UrlSortSecurityTestEntity $e): string => $e->getDisplayedField(), $entities);
    }

    /**
     * @param list<string> $expectedDisplayedFieldValues
     */
    private function assertRowsInOrder(array $expectedDisplayedFieldValues): void
    {
        foreach ($expectedDisplayedFieldValues as $i => $value) {
            $this->assertSelectorTextSame(
                sprintf('tbody tr:nth-child(%d) td[data-column="displayedField"]', $i + 1),
                $value,
                sprintf('Expected "%s" in row %d', $value, $i + 1),
            );
        }
    }
}
