<?php

declare( strict_types=1 );

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;

trait CrudTestUrlGeneration
{
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
}
