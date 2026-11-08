<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Fixtures;

// this class uses property hooks, so it can only be loaded in PHP 8.4 or higher
class ObjectWithVirtualIdProperty
{
    // a virtual property: it has no stored value and it's computed by its get hook
    public string $id {
        get => 'virtual-'.$this->number;
    }

    public function __construct(
        private readonly int $number,
    ) {
    }
}
