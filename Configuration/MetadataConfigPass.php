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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Introspects the metadata of the Doctrine entities to complete the
 * configuration of the properties.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class MetadataConfigPass implements ConfigPassInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function process(array $backendConfiguration)
    {
        foreach ($backendConfiguration['entities'] as $entityName => $entityConfiguration) {
            $em = $this->doctrine->getManagerForClass($entityConfiguration['class']);
            $entityMetadata = $em->getMetadataFactory()->getMetadataFor($entityConfiguration['class']);

            $entityConfiguration['primary_key_field_name'] = $entityMetadata->getSingleIdentifierFieldName();

            $entityProperties = $this->processEntityPropertiesMetadata($entityMetadata);
            $entityConfiguration['properties'] = $entityProperties;

            $backendConfiguration['entities'][$entityName] = $entityConfiguration;
        }

        return $backendConfiguration;
    }

        /**
     * Takes the entity metadata introspected via Doctrine and completes its
     * contents to simplify data processing for the rest of the application.
     *
     * @param ClassMetadata $entityMetadata The entity metadata introspected via Doctrine
     *
     * @return array The entity properties metadata provided by Doctrine
     */
    private function processEntityPropertiesMetadata(ClassMetadata $entityMetadata)
    {
        $entityPropertiesMetadata = array();

        if ($entityMetadata->isIdentifierComposite) {
            throw new \RuntimeException(sprintf("The '%s' entity isn't valid because it contains a composite primary key.", $entityMetadata->name));
        }

        // introspect regular entity fields
        foreach ($entityMetadata->fieldMappings as $fieldName => $fieldMetadata) {
            $entityPropertiesMetadata[$fieldName] = $fieldMetadata;
        }

        // introspect fields for entity associations
        foreach ($entityMetadata->associationMappings as $fieldName => $associationMetadata) {
            $entityPropertiesMetadata[$fieldName] = array(
                'type'            => 'association',
                'associationType' => $associationMetadata['type'],
                'fieldName'       => $fieldName,
                'fetch'           => $associationMetadata['fetch'],
                'isOwningSide'    => $associationMetadata['isOwningSide'],
                'targetEntity'    => $associationMetadata['targetEntity'],
            );
        }

        return $entityPropertiesMetadata;
    }
}
