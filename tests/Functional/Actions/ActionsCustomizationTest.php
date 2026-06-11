<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Actions;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\ActionsCustomizationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;

class ActionsCustomizationTest extends AbstractCrudTestCase
{
    protected EntityRepository $actionTestEntities;

    protected function getControllerFqcn(): string
    {
        return ActionsCustomizationCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        $this->actionTestEntities = $this->entityManager->getRepository(ActionTestEntity::class);
    }

    public function testCssClasses(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertSame('dropdown-item action-action1 dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action1")')->attr('class'));
        static::assertSame('dropdown-item foo dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action2")')->attr('class'));
        static::assertSame('dropdown-item action-action3 bar dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action3")')->attr('class'));
        static::assertSame('dropdown-item foo bar dropdown-item-variant-default', $crawler->filter('a.dropdown-item:contains("Action4")')->attr('class'));

        static::assertSame('btn btn-primary  action-new', trim($crawler->filter('.global-actions > a')->first()->attr('class')));
    }

    public function testDynamicLabels(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // get the first entity's name from the fixture
        $firstEntity = $this->actionTestEntities->findOneBy([]);
        $entityName = $firstEntity->getName();

        static::assertSame('Action 5: '.$entityName, $crawler->filter('a.dropdown-item[data-action-name="action5"]')->text());
        static::assertSame('Action 6: '.$entityName, $crawler->filter('a.dropdown-item[data-action-name="action6"]')->text());
        static::assertSame('Action 7: '.$entityName, $crawler->filter('a.dropdown-item[data-action-name="action7"]')->text());
        static::assertSame('Reset', $crawler->filter('a.dropdown-item[data-action-name="action8"]')->text());
    }

    public function testFormAction(): void
    {
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        static::assertCount(1, $crawler->filter('.global-actions form.action-action9'));
        static::assertCount(1, $crawler->filter('.global-actions form.action-action9 button'));
        static::assertSame('POST', $crawler->filter('.global-actions form.action-action9')->attr('method'));
    }
}
