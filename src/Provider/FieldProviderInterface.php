<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FieldProviderInterface
{
    public function getDefaultFields(string $pageName): array;
}
