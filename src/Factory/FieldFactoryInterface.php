<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FieldFactoryInterface
{
    public function processFields(EntityDtoInterface $entityDto, FieldCollection $fields): void;
}
