<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\ObjectLocking;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\LockTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\LockTestEntity;

class ObjectLockingTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return LockTestEntityCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testEditFormExposesTheLockVersionField(): void
    {
        $entity = $this->createEntity('initial title');

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        static::assertCount(1, $crawler->filter(sprintf('input[name="LockTestEntity[%s]"]', EA::LOCK_VERSION)));
    }

    public function testSubmittingAStaleLockVersionRejectsTheChangeAndRedirects(): void
    {
        $entity = $this->createEntity('initial title');

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        $form = $crawler->filter('form.ea-edit-form')->form();
        $form['LockTestEntity[title]'] = 'changed title';
        $form['LockTestEntity['.EA::LOCK_VERSION.']'] = '0';

        $this->client->submit($form);

        static::assertResponseRedirects();

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(LockTestEntity::class, $entity->getId());
        static::assertSame('initial title', $reloaded->getTitle());
    }

    public function testReloadingAfterAConflictShowsTheFreshFormWithoutResubmitting(): void
    {
        $this->client->followRedirects();
        $entity = $this->createEntity('initial title');

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        $form = $crawler->filter('form.ea-edit-form')->form();
        $form['LockTestEntity[title]'] = 'changed title';
        $form['LockTestEntity['.EA::LOCK_VERSION.']'] = '0';

        $crawler = $this->client->submit($form);

        static::assertSelectorExists('.alert');
        static::assertCount(1, $crawler->filter('form.ea-edit-form'));

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(LockTestEntity::class, $entity->getId());
        static::assertSame('initial title', $reloaded->getTitle());
    }

    public function testSubmittingTheCurrentLockVersionSavesTheChange(): void
    {
        $this->client->followRedirects();
        $entity = $this->createEntity('initial title');

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));
        $form = $crawler->filter('form.ea-edit-form')->form();
        $form['LockTestEntity[title]'] = 'changed title';

        $this->client->submit($form);

        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(LockTestEntity::class, $entity->getId());
        static::assertSame('changed title', $reloaded->getTitle());
    }

    private function createEntity(string $title): LockTestEntity
    {
        $entity = new LockTestEntity();
        $entity->setTitle($title);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $entity;
    }
}
