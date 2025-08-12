<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Security;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\Sort\WebsiteCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Website;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Security\CustomVoter;
use Symfony\Component\Security\Core\Authorization\Strategy\UnanimousStrategy;

class VoterTest extends AbstractCrudTestCase
{
    /**
     * @var EntityRepository
     */
    private $repository;

    protected function getControllerFqcn(): string
    {
        return WebsiteCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->repository = $this->entityManager->getRepository(Website::class);
    }

    public function testingVoter(): void
    {
        // Arrange
        $entities = $this->repository->findAll();

        /** @var CustomVoter $voter */
        $voter = $this->getContainer()->get(CustomVoter::class);
        $voter->ban($entities[0], 'name');

        // UnanimousStrategy
        $accessDecisionManager = $this->getContainer()->get('security.access.decision_manager');
        \Closure::bind(function () {
            $this->strategy = new UnanimousStrategy();
        }, $accessDecisionManager, $accessDecisionManager)();

        // Act
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('th[data-column="pages"]');
        $this->assertSelectorExists('th[data-column="name"]');
    }
}
