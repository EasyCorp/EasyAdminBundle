<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Search;

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * It looks for the values of entity which match the given query. It's used for
 * the autocomplete field types.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Autocomplete
{
    /** @var Configurator */
    private $configurator;
    /** @var Finder */
    private $finder;
    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct(Configurator $configurator, Finder $finder, PropertyAccessor $propertyAccessor)
    {
        $this->configurator = $configurator;
        $this->finder = $finder;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Finds the values of the given entity which match the query provided.
     *
     * @param string $entity
     * @param string $property
     * @param string $view
     * @param string $query
     *
     * @return array
     */
    public function find($entity, $property, $view, $query)
    {
        if (empty($entity) || empty($property) || empty($view) || empty($query)) {
            return array('results' => array());
        }

        $backendConfig = $this->configurator->getBackendConfig();
        if (!isset($backendConfig['entities'][$entity])) {
            throw new \InvalidArgumentException(sprintf('The "entity" argument must contain the name of an entity managed by EasyAdmin ("%s" given).', $entity));
        }

        if (!isset($backendConfig['entities'][$entity][$view]['fields'][$property])) {
            throw new \InvalidArgumentException(sprintf('The "property" argument must contain the name of a property configured in the "%s" view of the "%s" entity ("%s" given).', $view, $entity, $property));
        }

        if (!isset($backendConfig['entities'][$entity][$view]['fields'][$property]['targetEntity'])) {
            throw new \InvalidArgumentException(sprintf('The "%s" property configured in the "%s" view of the "%s" entity can\'t be of type "easyadmin_autocomplete" because it\'s not related to another entity.', $property, $view, $entity));
        }

        $targetEntityClass = $backendConfig['entities'][$entity][$view]['fields'][$property]['targetEntity'];
        $targetEntityConfig = $this->configurator->getEntityConfigByClass($targetEntityClass);
        $entities = $this->finder->findByAllProperties($targetEntityConfig, $query, $backendConfig['list']['max_results']);

        return array('results' => $this->processResults($entities, $targetEntityConfig));
    }

    private function processResults($entities, $targetEntityConfig)
    {
        $results = array();

        foreach ($entities as $entity) {
            $results[] = array(
                'id' => $this->propertyAccessor->getValue($entity, $targetEntityConfig['primary_key_field_name']),
                'text' => (string) $entity,
            );
        }

        return $results;
    }
}
