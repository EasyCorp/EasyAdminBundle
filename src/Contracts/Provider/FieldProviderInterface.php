<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

interface FieldProviderInterface
{
    /**
     * @return array<Field>
     */
    public function getDefaultFields(string $pageName): array;
}
