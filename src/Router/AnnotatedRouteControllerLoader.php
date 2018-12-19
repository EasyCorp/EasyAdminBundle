<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Route;

/**
 * AnnotatedRouteControllerLoader is an implementation of AnnotationClassLoader that sets the '_controller' default
 * based on the class and method names.
 *
 * This implementation is borrowed from the Symfony FrameworkBundle to allow support for releases of said bundle that
 * did not contain this class (i.e. FrameworkBundle < 3.4).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AnnotatedRouteControllerLoader extends AnnotationClassLoader
{
    protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annotation)
    {
        if ('__invoke' === $method->getName()) {
            $route->setDefault('_controller', $class->getName());
        } else {
            $route->setDefault('_controller', $class->getName().'::'.$method->getName());
        }
    }

    protected function getDefaultRouteName(\ReflectionClass $class, \ReflectionMethod $method)
    {
        return preg_replace(array(
            '/(bundle|controller)_/',
            '/action(_\d+)?$/',
            '/__/',
        ), array(
            '_',
            '\\1',
            '_',
        ), parent::getDefaultRouteName($class, $method));
    }
}
