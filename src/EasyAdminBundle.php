<?php

namespace EasyCorp\Bundle\EasyAdminBundle;

use EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler\FilterTypePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class EasyAdminBundle extends Bundle
{
    public const VERSION = '3.0.0-DEV';
    public const CONTEXT_ATTRIBUTE_NAME = 'easyadmin_context';

    // TODO: remove this method when EasyAdmin 3 is released.
    public function boot()
    {
        if ('cli' !== \PHP_SAPI) {
            throw new \RuntimeException('You are trying to use EasyAdmin 3 in your project. However, that version is not ready yet to test it in real projects. Instead, use EasyAdmin 2.x, which is the latest stable version.');
        }
    }
}
