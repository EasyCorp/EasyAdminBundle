<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Symfony\Component\HttpFoundation\ParameterBag;

final class ResponseParameters extends ParameterBag
{
    public static function new(array $parameters): self
    {
        return new self($parameters);
    }
}
