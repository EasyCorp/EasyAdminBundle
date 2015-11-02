<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Reflection;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Introspects information about the properties of the given entity class.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EntityMetadataInspector
{
    private $doctrineManager;

    public function __construct(ManagerRegistry $manager)
    {
        $this->doctrineManager = $manager;
    }

    /**
     * Takes the FQCN of an entity and returns all its metadata introspected
     * with Doctrine.
     *
     * @param string $entityClass
     *
     * @return ClassMetadata
     */
    public function getEntityMetadata($entityClass)
    {
        $em = $this->doctrineManager->getManagerForClass($entityClass);

        return $em->getMetadataFactory()->getMetadataFor($entityClass);
    }
}
