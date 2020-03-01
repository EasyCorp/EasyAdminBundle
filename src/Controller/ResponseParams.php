<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Symfony\Component\HttpFoundation\ParameterBag;

final class ResponseParams extends ParameterBag
{
    public static function new(array $params): self
    {
        return new self($params);
    }
}
