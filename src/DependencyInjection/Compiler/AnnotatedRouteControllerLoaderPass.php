<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Christian Flothmann <christian.flothmann@sensiolabs.de>
 */
class AnnotatedRouteControllerLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (class_exists('Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader')) {
            // FrameworkBundle 3.4+ ships it own AnnotationClassLoader implementation
            return;
        }

        if (!$container->has('routing.resolver') || $container->has('sensio_framework_extra.routing.loader.annot_class')) {
            return;
        }

        $container->register('easyadmin.routing.loader.annotation', 'EasyCorp\Bundle\EasyAdminBundle\Router\AnnotatedRouteControllerLoader')
            ->setPublic(false)
            ->addArgument(new Reference('annotation_reader'))
            ->addTag('routing.loader');

        $container->register('easyadmin.routing.loader.directory', 'Symfony\Component\Routing\Loader\AnnotationDirectoryLoader')
            ->setPublic(false)
            ->setArguments(array(
                new Reference('file_locator'),
                new Reference('easyadmin.routing.loader.annotation'),
            ))
            ->addTag('routing.loader');

        $container->register('easyadmin.routing.loader.file', 'Symfony\Component\Routing\Loader\AnnotationFileLoader')
            ->setPublic(false)
            ->addTag('routing.loader', array('priority' => -10))
            ->setArguments(array(
                new Reference('file_locator'),
                new Reference('easyadmin.routing.loader.annotation'),
            ));
    }
}
