<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

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

    public function process(array $backendConfig)
    {
        foreach ($backendConfig['entities'] as $entityName => $entityConfig) {
            if (null === $em = $this->doctrine->getManagerForClass($entityConfig['class'])) {
                throw new InvalidTypeException(sprintf('The configured class "%s" for the path "easy_admin.entities.%s" is no mapped entity. Did you forget to create the entity class or to define its namespace?', $entityConfig['class'], $entityName));
            }

            $entityMetadata = $em->getMetadataFactory()->getMetadataFor($entityConfig['class']);

            $entityConfig['primary_key_field_name'] = $entityMetadata->getSingleIdentifierFieldName();

            $entityConfig['properties'] = $this->processEntityPropertiesMetadata($entityMetadata);

            $backendConfig['entities'][$entityName] = $entityConfig;
        }

        return $backendConfig;
    }

    /**
     * Takes the entity metadata introspected via Doctrine and completes its
     * contents to simplify data processing for the rest of the application.
     *
     * @param ClassMetadata $entityMetadata The entity metadata introspected via Doctrine
     *
     * @return array The entity properties metadata provided by Doctrine
     *
     * @throws \RuntimeException
     */
    private function processEntityPropertiesMetadata(ClassMetadata $entityMetadata)
    {
        $entityPropertiesMetadata = [];

        if ($entityMetadata->isIdentifierComposite) {
            throw new \RuntimeException(sprintf("The '%s' entity isn't valid because it contains a composite primary key.", $entityMetadata->name));
        }

        // introspect regular entity fields
        foreach ($entityMetadata->fieldMappings as $fieldName => $fieldMetadata) {
            $entityPropertiesMetadata[$fieldName] = $fieldMetadata;
        }

        // introspect fields for entity associations
        foreach ($entityMetadata->associationMappings as $fieldName => $associationMetadata) {
            $entityPropertiesMetadata[$fieldName] = array_merge($associationMetadata, [
                'type' => 'association',
                'associationType' => $associationMetadata['type'],
            ]);

            // associations different from *-to-one cannot be sorted
            if ($associationMetadata['type'] & ClassMetadata::TO_MANY) {
                $entityPropertiesMetadata[$fieldName]['sortable'] = false;
            }
        }

        return $entityPropertiesMetadata;
    }
}
