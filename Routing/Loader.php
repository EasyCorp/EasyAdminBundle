<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Routing;

use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\Configurator;
use Symfony\Component\Config\Loader\Loader as BaseLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loader that generates EasyAdmin routes per entity.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class Loader extends BaseLoader
{
    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var Configurator
     */
    private $configurator;

    public function __construct(Configurator $configurator)
    {
        $this->configurator = $configurator;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "easyadmin" loader twice');
        }

        $routes = new RouteCollection();

        // commons actions
        $actions = array('list', 'show', 'new', 'edit', 'delete');

        foreach ($this->configurator->getBackendConfig('entities') as $entityName => $config) {
            // this name is unique in backend configuration
            $entityRouteName = strtolower($entityName);

            foreach ($actions as $action) {
                if ('list' === $action || 'new' === $action) {
                    $path = sprintf('/%s/%s', $entityRouteName, $action);
                } else {
                    $path = sprintf('/%s/{id}/%s', $entityRouteName, $action);
                }
                $defaults = array(
                    '_controller' => 'EasyAdminBundle:Admin:index',
                    'entity' => $entityName,
                    'action' => $action,
                );
                $requirements = array();

                $route = new Route($path, $defaults, $requirements);

                // the route name must be unique
                $routeName = sprintf('easyadmin_%s_%s', $entityRouteName, $action);
                $routes->add($routeName, $route);
            }
        }

        $this->loaded = true;

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'easyadmin' === $type;
    }
}
