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

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * It looks for the values of entity which match the given query. It's used for
 * the autocomplete field types.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Autocomplete
{
    /** @var ConfigManager */
    private $configManager;
    /** @var Finder */
    private $finder;
    /** @var PropertyAccessor */
    private $propertyAccessor;

    public function __construct(ConfigManager $configManager, Finder $finder, PropertyAccessor $propertyAccessor)
    {
        $this->configManager = $configManager;
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
     * @param int    $page
     *
     * @return array
     */
    public function find($entity, $property, $view, $query, $page = 1)
    {
        if (empty($entity) || empty($property) || empty($view) || empty($query)) {
            return array('results' => array());
        }

        $backendConfig = $this->configManager->getBackendConfig();
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
        $targetEntityConfig = $this->configManager->getEntityConfigByClass($targetEntityClass);
        if (null === $targetEntityConfig) {
            throw new \InvalidArgumentException(sprintf('The configuration of the "%s" entity is not available (this entity is used as the target of the "%s" autocomplete field in the "%s" view of the "%s" entity).', $targetEntityClass, $property, $view, $entity));
        }

        $paginator = $this->finder->findByAllProperties($targetEntityConfig, $query, $page, $backendConfig['show']['max_results']);

        return array('results' => $this->processResults($paginator->getCurrentPageResults(), $targetEntityConfig));
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
