<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Fields\Choice;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\EnumChoices\EnumChoicesWithEmptyChoicesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\EnumChoices\EnumChoicesWithoutChoicesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestStatus;

/**
 * A choice field of an enum property lists all the enum cases only when
 * setChoices() isn't called; an empty list of choices accepts no value.
 */
class EnumChoicesTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return EnumChoicesWithoutChoicesCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testFieldWithoutChoicesAcceptsAllEnumCases(): void
    {
        $entity = $this->createEntity(FieldTestStatus::Draft);

        $crawler = $this->client->request('GET', $this->generateIndexUrl());
        static::assertStringContainsString('Draft', $crawler->filter(sprintf('tr[data-id="%d"] td[data-column="enumChoiceField"]', $entity->getId()))->text());

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId()));

        static::assertSame(['draft', 'published', 'archived'], $crawler->filter('#FieldTestEntity_enumChoiceField option[value!=""]')->extract(['value']));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form['FieldTestEntity[enumChoiceField]'] = 'archived';
        $this->client->submit($form);

        static::assertResponseRedirects();
        static::assertSame(FieldTestStatus::Archived, $this->findEntity($entity->getId())->getEnumChoiceField());
    }

    public function testFieldWithEmptyChoicesAcceptsNoEnumCase(): void
    {
        $entity = $this->createEntity(FieldTestStatus::Draft);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId(), controllerFqcn: EnumChoicesWithEmptyChoicesCrudController::class));

        static::assertResponseIsSuccessful();
        static::assertCount(0, $crawler->filter('#FieldTestEntity_enumChoiceField option[value!=""]'));

        $form = $crawler->filter('form[name="FieldTestEntity"]')->form();
        $form->disableValidation();
        $form['FieldTestEntity[enumChoiceField]'] = 'archived';
        $this->client->submit($form);

        static::assertResponseStatusCodeSame(422);
        static::assertSame(FieldTestStatus::Draft, $this->findEntity($entity->getId())->getEnumChoiceField());
    }

    public function testFieldWithEmptyChoicesDisplaysNoLabelOnIndexAndDetail(): void
    {
        $entity = $this->createEntity(FieldTestStatus::Draft);

        $crawler = $this->client->request('GET', $this->generateIndexUrl(controllerFqcn: EnumChoicesWithEmptyChoicesCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('Draft', $crawler->filter(sprintf('tr[data-id="%d"] td[data-column="enumChoiceField"]', $entity->getId()))->text());

        $crawler = $this->client->request('GET', $this->generateDetailUrl($entity->getId(), controllerFqcn: EnumChoicesWithEmptyChoicesCrudController::class));
        static::assertResponseIsSuccessful();
        static::assertStringNotContainsString('Draft', $crawler->filter('.content-body')->text());
    }

    public function testFieldWithEmptyChoicesClearsTheValueWhenSubmittedAsRendered(): void
    {
        $entity = $this->createEntity(FieldTestStatus::Draft);

        $crawler = $this->client->request('GET', $this->generateEditFormUrl($entity->getId(), controllerFqcn: EnumChoicesWithEmptyChoicesCrudController::class));
        $this->client->submit($crawler->filter('form[name="FieldTestEntity"]')->form(['FieldTestEntity[textField]' => 'Field 1']));

        static::assertResponseRedirects();
        static::assertNull($this->findEntity($entity->getId())->getEnumChoiceField());
    }

    private function createEntity(FieldTestStatus $status): FieldTestEntity
    {
        $entity = (new FieldTestEntity())->setEnumChoiceField($status);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    private function findEntity(int $id): FieldTestEntity
    {
        $this->entityManager->clear();

        return $this->entityManager->getRepository(FieldTestEntity::class)->find($id);
    }
}
