<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts;

interface LockableInterface
{
    public function getLockVersion(): ?int;
}
