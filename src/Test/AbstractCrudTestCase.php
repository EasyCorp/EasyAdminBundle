<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestActions;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestFormAsserts;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestIndexAsserts;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestUrlGeneration;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @template Crud of CrudControllerInterface
 */
abstract class AbstractCrudTestCase extends WebTestCase
{
    use CrudTestActions;
    use CrudTestFormAsserts;
    use CrudTestIndexAsserts;
    use CrudTestUrlGeneration;

    protected ?KernelBrowser $client = null;
    protected ?AdminUrlGeneratorInterface $adminUrlGenerator = null;
    protected ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;

        /** @var AdminUrlGenerator $adminUrlGenerator */
        $adminUrlGenerator = $container->get(AdminUrlGenerator::class);
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    protected function tearDown(): void
    {
        if (null !== $this->entityManager && $this->entityManager->isOpen()) {
            $this->entityManager->clear();
            $this->entityManager->getConnection()->close();
        }

        $this->client = null;
        $this->entityManager = null;
        $this->adminUrlGenerator = null;

        parent::tearDown();

        // Reset the cached kernel class to ensure tests that override getKernelClass()
        // properly boot their designated kernel. This is needed because Symfony 5.4.x
        // caches static::$class and doesn't reset it in ensureKernelShutdown().
        static::$class = null;
    }

    /**
     * @return class-string<Crud> returns the tested Controller Fqcn
     */
    abstract protected function getControllerFqcn(): string;

    /**
     * @return class-string<DashboardControllerInterface> returns the tested Controller Fqcn
     */
    abstract protected function getDashboardFqcn(): string;
}
