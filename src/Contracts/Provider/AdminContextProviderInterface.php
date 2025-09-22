<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;

/**
 * Inject this in services that need to get the admin context object.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminContextProviderInterface extends AdminContextInterface
{
    public function hasContext(): bool;

    // the $throw parameter is deprecated and will be removed in 5.0
    public function getContext(bool $throw = false): ?AdminContextInterface;
}
