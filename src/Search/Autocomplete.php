<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Search;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * It looks for the values of entity which match the given query. It's used for
 * the autocomplete field types.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class Autocomplete
{
    private const MAX_RESULTS = 10;

    private $configManager;
    private $finder;
    private $propertyAccessor;

    public function __construct(ConfigManager $configManager, Finder $finder, PropertyAccessorInterface $propertyAccessor)
    {
        $this->configManager = $configManager;
        $this->finder = $finder;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Finds the values of the given entity which match the query provided.
     *
     * @throws \InvalidArgumentException
     */
    public function find(string $entity = null, string $query = null, int $page = 1, int $maxResults = null, string $sortField = null, string $sortDirection = null, string $dqlFilter = null): array
    {
        if (empty($entity) || empty($query)) {
            return ['results' => []];
        }

        $backendConfig = $this->configManager->getBackendConfig();
        if (!isset($backendConfig['entities'][$entity])) {
            throw new \InvalidArgumentException(\sprintf('The "entity" argument must contain the name of an entity managed by EasyAdmin ("%s" given).', $entity));
        }
        $entityConfig = $backendConfig['entities'][$entity];

        $maxResults = $maxResults ?? self::MAX_RESULTS;
        $dqlFilter = $dqlFilter ?? $entityConfig['autocomplete']['dql_filter'] ?? null;

        $paginator = $this->finder->findByAllProperties($entityConfig, $query, $page, $maxResults, $sortField, $sortDirection, $dqlFilter);

        return [
            'results' => $this->processResults($paginator->getCurrentPageResults(), $entityConfig),
            'has_next_page' => $paginator->hasNextPage(),
        ];
    }

    private function processResults($entities, array $targetEntityConfig): array
    {
        $results = [];

        foreach ($entities as $entity) {
            $results[] = [
                'id' => $this->propertyAccessor->getValue($entity, $targetEntityConfig['primary_key_field_name']),
                'text' => (string) $entity,
            ];
        }

        return $results;
    }
}
