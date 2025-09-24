<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider;

interface FieldProviderInterface
{
    public function getDefaultFields(string $pageName): array;
}