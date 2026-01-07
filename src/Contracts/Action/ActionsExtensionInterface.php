<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Action;

use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

/**
 * Contract for services that modify EasyAdmin actions at runtime.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ActionsExtensionInterface
{
    public function supports(AdminContext $context): bool;

    public function extend(Actions $actions, AdminContext $context): void;
}
