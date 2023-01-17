<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\ColumnStorage;

interface UserParametersStorageInterface
{
    public function setOrAddParameter(string $title, mixed $value): object;

    public function getParameter(string $title, mixed $default = [], bool $scalar = false): mixed;
}
