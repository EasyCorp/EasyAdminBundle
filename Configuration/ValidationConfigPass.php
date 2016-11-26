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
        $this->validateToStringMethod($backendConfig);

        return $backendConfig;
    }

    /**
     * It checks that the __toString() method is defined in the entity classes
     * that need it. This avoids the usual error "Object of class ... could not
     * be converted to string".
     *
     * @param array $backendConfig
     */
    private function validateToStringMethod(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            $enabledViews = array_diff(array('list', 'search', 'show'), $entityConfig['disabled_actions']);
            foreach ($enabledViews as $view) {
                foreach ($entityConfig[$view]['fields'] as $fieldName => $fieldConfig) {
                    // Doctrine associations that don't define a custom template must define a __toString() method
                    $fieldUsesDefaultTemplate = '@EasyAdmin/default/field_association.html.twig' === $fieldConfig['template'];
                    if ('association' === $fieldConfig['dataType'] && $fieldUsesDefaultTemplate) {
                        if (!method_exists($fieldConfig['targetEntity'], '__toString')) {
                            throw new \InvalidArgumentException(sprintf('The "%s" class must define a "__toString()" method because it is used as the "%s" field in the "%s" view of the "%s" entity.', $fieldConfig['targetEntity'], $fieldName, $view, $entityName));
                        }
                    }
                }
            }

            $enabledViews = array_diff(array('edit', 'new'), $entityConfig['disabled_actions']);
            foreach ($enabledViews as $view) {
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
    }
}
