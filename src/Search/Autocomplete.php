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
     * @param string $entity
     * @param string $query
     * @param int    $page
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function find($entity, $query, $page = 1)
    {
        if (empty($entity) || empty($query)) {
            return ['results' => []];
        }

        $backendConfig = $this->configManager->getBackendConfig();
        if (!isset($backendConfig['entities'][$entity])) {
            throw new \InvalidArgumentException(sprintf('The "entity" argument must contain the name of an entity managed by EasyAdmin ("%s" given).', $entity));
        }

        $paginator = $this->finder->findByAllProperties($backendConfig['entities'][$entity], $query, $page, $backendConfig['show']['max_results']);

        return [
            'results' => $this->processResults($paginator->getCurrentPageResults(), $backendConfig['entities'][$entity]),
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
