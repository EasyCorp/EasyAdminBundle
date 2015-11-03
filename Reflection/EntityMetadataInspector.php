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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Introspects information about the properties of the given entity class.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EntityMetadataInspector
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
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
        $em = $this->doctrine->getManagerForClass($entityClass);

        return $em->getMetadataFactory()->getMetadataFor($entityClass);
    }
}
