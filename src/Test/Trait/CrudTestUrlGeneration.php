<?php

declare( strict_types=1 );

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Test\Exception\InvalidClassPropertyTypeException;
use EasyCorp\Bundle\EasyAdminBundle\Test\Exception\MissingClassMethodException;

trait CrudTestUrlGeneration
{
	/**
	 * @param array<string, string> $options
	 * @throws InvalidClassPropertyTypeException
	 * @throws MissingClassMethodException
	 */
	protected function getCrudUrl(string $action, string|int $entityId = null, array $options = []): string
	{
		$this->checkTestClassSetup();

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

	/**
	 * @throws InvalidClassPropertyTypeException
	 * @throws MissingClassMethodException
	 */
	protected function checkTestClassSetup(): void
	{
		if (!property_exists($this, 'adminUrlGenerator') || !($this->adminUrlGenerator instanceof AdminUrlGenerator)) {
			throw new InvalidClassPropertyTypeException('adminUrlGenerator', AdminUrlGenerator::class);
		}

		if (!(method_exists($this, 'getControllerFqcn') and method_exists($this, 'getDashboardFqcn'))) {
			throw new MissingClassMethodException(['getControllerFqcn()', 'getDashboardFqcn()']);
		}
	}

	/**
	 * @throws InvalidClassPropertyTypeException
	 * @throws MissingClassMethodException
	 */
	protected function generateIndexUrl(string $query = null): string
	{
		$options = [];

		if (null !== $query) {
			$options['query'] = $query;
		}

		return $this->getCrudUrl(Action::INDEX, null, $options);
	}

	/**
	 * @throws InvalidClassPropertyTypeException
	 * @throws MissingClassMethodException
	 */
	protected function generateNewFormUrl(): string
	{
		return $this->getCrudUrl(Action::NEW);
	}

	/**
	 * @throws InvalidClassPropertyTypeException
	 * @throws MissingClassMethodException
	 */
	protected function generateEditFormUrl(string|int $id): string
	{
		return $this->getCrudUrl(Action::EDIT, $id);
	}

	/**
	 * @throws InvalidClassPropertyTypeException
	 * @throws MissingClassMethodException
	 */
	protected function generateDetailUrl(string|int $id): string
	{
		return $this->getCrudUrl(Action::DETAIL, $id);
	}

	/**
	 * @throws InvalidClassPropertyTypeException
	 * @throws MissingClassMethodException
	 */
	protected function generateFilterRenderUrl(): string
	{
		// Use the index URL as referrer but remove scheme, host and port
		$referrer = preg_replace('/^.*(\/.*)$/', '$1', $this->generateIndexUrl());

		return $this->getCrudUrl('renderFilters', null, [ 'referrer' => $referrer]);
	}
}
