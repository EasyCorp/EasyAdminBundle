<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminContextProviderInterface
{
    public function getContext(): ?AdminContext;
}
