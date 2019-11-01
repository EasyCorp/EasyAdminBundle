<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\EntityAdminConfig;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface EntityAdminControllerInterface
{
    public function getEntityAdminConfig(): EntityAdminConfig;

    /**
     * @return FieldInterface[]
     */
    public function getFields(string $action): iterable;

    /**
     * The fully-qualified class name (FQCN) of the Doctrine ORM entity
     * managed by this controller (e.g. 'App\Entity\User')
     */
    public function getEntityClass(): string;

    /**
     * The singular name of the managed entity as displayed to end users (e.g. 'User', 'Invoice')
     */
    public function getNameInSingular(): string;

    /**
     * The plural name of the managed entity as displayed to end users (e.g. 'Users', 'Invoices')
     */
    public function getNameInPlural(): string;
}
