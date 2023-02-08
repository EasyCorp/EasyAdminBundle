<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Test\TestCase;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractCrudTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected AdminUrlGenerator $adminUrlGenerator;
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

	/******
	 *  Url Generators
	 */

    /**
     * @param array<string, string> $options
     */
    protected function getCrudUrl(string $action, string|int $entityId = null, array $options = []): string
    {
	    $this->adminUrlGenerator
		    ->setDashboard($this->getDashboardFqcn())
		    ->setController($this->getControllerFqcn())
		    ->setAction($action)
	    ;

        if (null !== $entityId) {
            $this->adminUrlGenerator->setEntityId($entityId);
        }

		foreach ($options as $key => $value) {
			$this->adminUrlGenerator->set($key, $value);
		}

        return $this->adminUrlGenerator->generateUrl();
    }

    protected function generateIndexUrl(string $query = null): string
    {
        $options = [];

        if (null !== $query) {
            $options['query'] = $query;
        }

        return $this->getCrudUrl(Action::INDEX, null, $options);
    }

    protected function generateNewFormUrl(): string
    {
        return $this->getCrudUrl(Action::NEW);
    }

    protected function generateEditFormUrl(string|int $id): string
    {
        return $this->getCrudUrl(Action::EDIT, $id);
    }

    protected function generateDetailUrl(string|int $id): string
    {
        return $this->getCrudUrl(Action::DETAIL, $id);
    }

    protected function generateFilterFormUrl(): string
    {
        // Use the index URL as referrer but remove scheme, host and port
        $referrer = preg_replace('/^.*(\/.*)$/', '$1', $this->generateIndexUrl());

        return $this->getCrudUrl('renderFilters', null, [ 'referrer' => $referrer]);
    }

	/******
	 * Asserts
	 */

	protected function assertIndexRecordCount(int $expectedIndexRecordCount): void
	{
		if ( 0 > $expectedIndexRecordCount) {
			throw new \InvalidArgumentException();
		}

		if ( 0 === $expectedIndexRecordCount) {
			static::assertSelectorTextSame('.no-results', 'No results found.');
		} else {
			static::assertSelectorTextSame('.list-pagination-counter strong', (string) $expectedIndexRecordCount);
		}
	}
}
