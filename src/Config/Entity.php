<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

final class Entity
{
    private $fqcn;
    private $id;
    private $permission;

    public function __construct(string $fqcn, $id, ?string $permission)
    {
        $this->fqcn = $fqcn;
        $this->id = $id;
        $this->permission = $permission;
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }
}
