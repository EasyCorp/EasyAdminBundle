<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Configuration;

/**
 * Performs the last validations on the processed backend configuration before
 * executing the application.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ValidationConfigPass implements ConfigPassInterface
{
    public function process(array $backendConfig)
    {
        $backendConfig = $this->processAssociationsConfig($backendConfig);

        return $backendConfig;
    }

    /**
     * It checks that the entity classes used in associations define a __toString()
     * method to avoid the usual error "Object of class ... could not be converted to string".
     *
     * @param array $backendConfig
     *
     * @return array
     */
    private function processAssociationsConfig(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            foreach (array('list', 'search', 'show') as $view) {
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    // Doctrine associations that don't define a custom template must define a __toString() method
                    if ('association' === $fieldConfig['dataType'] && 0 === strpos($fieldConfig['template'], '@EasyAdmin/default/')) {
                        if (!method_exists($fieldConfig['targetEntity'], '__toString')) {
                            throw new \InvalidArgumentException(sprintf('The "%s" class must define a "__toString()" method because it is used as the "%s" field in the "%s" view of the "%s" entity.', $fieldConfig['targetEntity'], $fieldName, $view, $entityName));
                        }
                    }
                }
            }

            foreach (array('edit', 'new') as $view) {
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    // fields used in autocomplete form types must define a __toString() method
                    if ('easyadmin_autocomplete' === $fieldConfig['fieldType']) {
                        if (!method_exists($fieldConfig['targetEntity'], '__toString')) {
                            throw new \InvalidArgumentException(sprintf('The "%s" class must define a "__toString()" method because it is used as the "%s" autocomplete field in the "%s" view of the "%s" entity.', $fieldConfig['targetEntity'], $fieldName, $view, $entityName));
                        }
                    }
                }
            }
        }

        return $backendConfig;
    }
}
