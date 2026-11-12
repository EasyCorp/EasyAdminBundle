<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Autocomplete\Fixtures;

/**
 * A class that is not a controller and that records if somebody instantiated it.
 */
final class ConstructorSpy
{
    public static bool $wasInstantiated = false;

    public function __construct()
    {
        self::$wasInstantiated = true;
    }
}
