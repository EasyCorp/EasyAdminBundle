<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Icon;

interface EntityIconGeneratorInterface
{
    /**
     * For a given entity FQCN (for example "App\Entity\Invoice") generates a string representing
     * an icon (for example "fa fa-file-invoice").
     *
     * @param class-string $entity
     */
    public function generate(string $entity): ?string;
}
