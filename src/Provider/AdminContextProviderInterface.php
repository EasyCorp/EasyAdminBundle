<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

interface AdminContextProviderInterface
{
    public function getContext(): ?AdminContext;
}
