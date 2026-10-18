<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Uid;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\UidRelationTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\UlidTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\UuidTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UidRelationTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UlidTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UuidTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UidRelationTestEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UlidTestEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UuidTestEntityFactory;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * The entity filter and the autocomplete form fields convert the Uuid and Ulid ids of the
 * associated entities to the format that each database uses to store them.
 */
class UidAssociationTest extends AbstractCrudTestCase
{
    /** @var array{uuidRelation: list<UuidTestEntity>, ulidRelation: list<UlidTestEntity>} */
    private array $relatedEntities;

    /** @var list<UidRelationTestEntity> */
    private array $entities;

    protected function getControllerFqcn(): string
    {
        return UidRelationTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->relatedEntities = [
            'uuidRelation' => [UuidTestEntityFactory::createOne(['name' => 'Uuid 1']), UuidTestEntityFactory::createOne(['name' => 'Uuid 2'])],
            'ulidRelation' => [UlidTestEntityFactory::createOne(['name' => 'Ulid 1']), UlidTestEntityFactory::createOne(['name' => 'Ulid 2'])],
        ];

        $this->entities = [
            UidRelationTestEntityFactory::createOne(['name' => 'Entity 1', 'uuidRelation' => $this->relatedEntities['uuidRelation'][0], 'ulidRelation' => $this->relatedEntities['ulidRelation'][0]]),
            UidRelationTestEntityFactory::createOne(['name' => 'Entity 2', 'uuidRelation' => $this->relatedEntities['uuidRelation'][1], 'ulidRelation' => $this->relatedEntities['ulidRelation'][1]]),
            UidRelationTestEntityFactory::createOne(['name' => 'Entity 3']),
        ];
    }

    /**
     * @param list<int> $expectedEntityIndexes
     */
    #[TestWith(['uuidRelation', ComparisonType::EQ, [0]])]
    #[TestWith(['uuidRelation', ComparisonType::NEQ, [1, 2]])]
    #[TestWith(['ulidRelation', ComparisonType::EQ, [0]])]
    #[TestWith(['ulidRelation', ComparisonType::NEQ, [1, 2]])]
    public function testEntityFilter(string $property, string $comparison, array $expectedEntityIndexes): void
    {
        $this->client->request('GET', $this->getCrudUrl(Action::INDEX, null, [
            sprintf('filters[%s][comparison]', $property) => $comparison,
            sprintf('filters[%s][value]', $property) => (string) $this->relatedEntities[$property][0]->getId(),
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertIndexFullEntityCount(\count($expectedEntityIndexes));
        foreach ($expectedEntityIndexes as $index) {
            $this->assertIndexEntityActionExists(Action::EDIT, $this->entities[$index]->getId());
        }
    }

    #[TestWith(['uuidRelation', UuidTestEntityCrudController::class])]
    #[TestWith(['ulidRelation', UlidTestEntityCrudController::class])]
    public function testAutocompleteResultsUseTheUidAsEntityId(string $property, string $relatedCrudControllerFqcn): void
    {
        $this->client->request('GET', $this->getCrudUrl('autocomplete', null, [
            'autocompleteContext[crudControllerFqcn]' => UidRelationTestEntityCrudController::class,
            'autocompleteContext[propertyName]' => $property,
            'autocompleteContext[originatingPage]' => 'new',
        ], DashboardController::class, $relatedCrudControllerFqcn));

        $this->assertResponseIsSuccessful();
        $results = json_decode($this->client->getResponse()->getContent(), true)['results'];
        $expectedIds = array_map(static fn (UuidTestEntity|UlidTestEntity $entity): string => (string) $entity->getId(), $this->relatedEntities[$property]);
        $actualIds = array_column($results, 'entityId');
        sort($expectedIds);
        sort($actualIds);
        $this->assertSame($expectedIds, $actualIds);
    }

    public function testAutocompleteFieldsSubmitUidIds(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#main form')->form();
        $form->disableValidation();
        $form['UidRelationTestEntity[name]'] = 'Entity 4';
        $form['UidRelationTestEntity[uuidRelation][autocomplete]'] = (string) $this->relatedEntities['uuidRelation'][1]->getId();
        $form['UidRelationTestEntity[ulidRelation][autocomplete]'] = (string) $this->relatedEntities['ulidRelation'][1]->getId();
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->entityManager->clear();
        $entity = $this->entityManager->getRepository(UidRelationTestEntity::class)->findOneBy(['name' => 'Entity 4']);
        $this->assertNotNull($entity);
        $this->assertTrue($this->relatedEntities['uuidRelation'][1]->getId()->equals($entity->getUuidRelation()?->getId()));
        $this->assertTrue($this->relatedEntities['ulidRelation'][1]->getId()->equals($entity->getUlidRelation()?->getId()));
    }
}
