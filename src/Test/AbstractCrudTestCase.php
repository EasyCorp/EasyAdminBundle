<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestActions;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestIndexAsserts;
use EasyCorp\Bundle\EasyAdminBundle\Test\Trait\CrudTestUrlGeneration;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractCrudTestCase extends WebTestCase
{
    use CrudTestActions;
    use CrudTestIndexAsserts;
    use CrudTestUrlGeneration;

    protected KernelBrowser $client;
    protected AdminUrlGeneratorInterface $adminUrlGenerator;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->adminUrlGenerator = $container->get(AdminUrlGenerator::class);
    }

    /**
     * @return string returns the tested Controller Fqcn
     */
    abstract protected function getControllerFqcn(): string;

    /**
     * @return string returns the tested Controller Fqcn
     */
    abstract protected function getDashboardFqcn(): string;
}
