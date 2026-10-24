<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Uid;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UlidTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UuidTestEntity;

/**
 * Each database stores Uuid and Ulid values in a different way (native UUID type on
 * PostgreSQL, binary strings elsewhere), so these tests use the ids in every place where
 * EasyAdmin converts them: URLs of the entity actions and the search.
 */
abstract class UidIdentifierTestCase extends AbstractCrudTestCase
{
    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    abstract protected function createEntity(string $name): UuidTestEntity|UlidTestEntity;

    /**
     * @return class-string<UuidTestEntity|UlidTestEntity>
     */
    abstract protected function getEntityFqcn(): string;

    public function testDetailPage(): void
    {
        $entity = $this->createEntity('Entity 1');

        $this->client->request('GET', $this->generateDetailUrl((string) $entity->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.field-text .field-value', 'Entity 1');
    }

    public function testEditForm(): void
    {
        $entity = $this->createEntity('Entity 1');
        $entityId = $entity->getId();

        $crawler = $this->client->request('GET', $this->generateEditFormUrl((string) $entityId));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#main form')->form();
        $form[$this->getFormName().'[name]'] = 'Entity 1 edited';
        $this->client->submit($form);

        $this->assertResponseRedirects();
        $this->entityManager->clear();
        $this->assertSame('Entity 1 edited', $this->entityManager->find($this->getEntityFqcn(), $entityId)?->getName());
    }

    public function testDelete(): void
    {
        $entityId = $this->createEntity('Entity 1')->getId();
        $this->createEntity('Entity 2');

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        $this->assertResponseIsSuccessful();
        $this->assertIndexEntityActionExists(Action::DELETE, (string) $entityId);

        $this->client->request('POST', $this->getCrudUrl(Action::DELETE, (string) $entityId), [
            'token' => $crawler->filter('#action-confirmation-form input[name="token"]')->attr('value'),
        ]);

        $this->assertResponseRedirects();
        $this->entityManager->clear();
        $this->assertNull($this->entityManager->find($this->getEntityFqcn(), $entityId));
        $this->assertSame(1, $this->entityManager->getRepository($this->getEntityFqcn())->count([]));
    }

    public function testSearchByFullId(): void
    {
        $entity = $this->createEntity('Entity 1');
        $this->createEntity('Entity 2');

        $this->client->request('GET', $this->generateIndexUrl((string) $entity->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertIndexFullEntityCount(1);
        $this->assertIndexEntityActionExists(Action::EDIT, (string) $entity->getId());
    }

    private function getFormName(): string
    {
        return (new \ReflectionClass($this->getEntityFqcn()))->getShortName();
    }
}
