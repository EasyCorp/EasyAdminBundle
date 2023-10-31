<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;


use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @internal and @experimental don't use this in your own apps
 */
interface FormLayoutFactoryInterface
{
    public function createLayout(FieldCollection $fields, string $pageName): FieldCollection;
}
