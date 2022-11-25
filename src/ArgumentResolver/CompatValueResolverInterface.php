<?php

namespace EasyCorp\Bundle\EasyAdminBundle\ArgumentResolver;

use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;

// TODO: remove this file when all supported Symfony versions include ValueResolverInterface
if (class_exists(ValueResolverInterface::class)) {
    class_alias(ValueResolverInterface::class, CompatValueResolverInterface::class);
} else {
    class_alias(ArgumentValueResolverInterface::class, CompatValueResolverInterface::class);
}
