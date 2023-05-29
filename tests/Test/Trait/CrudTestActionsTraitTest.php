<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Test\Trait;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestActions;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestUrlGeneration;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CrudTestActionsTraitTest extends WebTestCase
{
    use CrudTestActions;
    use CrudTestUrlGeneration;

    protected KernelBrowser $client;
    protected AdminUrlGenerator $adminUrlGenerator;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
        $this->client->setServerParameters(['PHP_AUTH_USER' => 'admin', 'PHP_AUTH_PW' => '1234']);

        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->adminUrlGenerator = $container->get(AdminUrlGenerator::class);
    }

    /**
     * @return string returns the tested Controller Fqcn
     */
    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    /**
     * @return string returns the tested Controller Fqcn
     */
    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testIndexClickOnNewAction(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->clickOnIndexGlobalAction(Action::NEW);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString($this->generateNewFormUrl(), $this->client->getRequest()->getUri());
    }

    public function testIndexClickOnNonExistentActionFail(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        self::expectException(AssertionFailedError::class);
        $this->clickOnIndexGlobalAction(Action::EDIT);
    }
}
