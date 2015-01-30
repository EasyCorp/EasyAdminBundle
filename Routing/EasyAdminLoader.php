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

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class EasyAdminLoader implements LoaderInterface
{
    private $config;

    function __construct($config)
    {
        $this->config = $config;
    }

    public function load($resource, $type = null)
    {
        $collection = new RouteCollection();

        $this->createHomepageRoute($collection);

        foreach ($this->config['entities'] as $name => $config) {
            $this->createEntityRoutes($collection, $name, $config);
        }

        return $collection;
    }

    public function supports($resource, $type = null)
    {
        return 'easy_admin' === $type;
    }

    public function getResolver()
    {

    }

    public function setResolver(LoaderResolverInterface $resolver)
    {

    }

    protected function createHomepageRoute(RouteCollection $collection)
    {
        if (count($this->config['entities'])) {
            $entity = current($this->config['entities']);

            $collection->add('easy_admin_homepage', new Route('/', array(
                '_controller' => 'FrameworkBundle:Redirect:redirect',
                'route' => 'easy_admin_' . strtolower($entity['name']) . '_list',
            )));
        }
    }

    protected function createEntityRoutes(RouteCollection $collection, $name, $config)
    {
        $routeEntityPart = strtolower($name);
        $routeNameTemplate = 'easy_admin_' . $routeEntityPart . '_%s';

        if (isset($config['list'])) {
            $route = new Route(sprintf('/%s/%s/{page}', $routeEntityPart, 'list'), array(
                '_controller' => 'EasyAdminBundle:Admin:list',
                'entity' => $name,
                'action' => 'list',
                'page' => 1,
            ));
            $collection->add(sprintf($routeNameTemplate, 'list'), $route);
        }

        if (isset($config['show'])) {
            $route = new Route(sprintf('/%s/%s/{id}', $routeEntityPart, 'show'), array(
                '_controller' => 'EasyAdminBundle:Admin:show',
                'entity' => $name,
                'action' => 'show',
            ));
            $collection->add(sprintf($routeNameTemplate, 'show'), $route);
        }

        if (true) {
            $route = new Route(sprintf('/%s/%s', $routeEntityPart, 'search'), array(
                '_controller' => 'EasyAdminBundle:Admin:search',
                'entity' => $name,
                'action' => 'search',
            ));
            $collection->add(sprintf($routeNameTemplate, 'search'), $route);
        }

        if (isset($config['new'])) {
            $route = new Route(sprintf('/%s/%s', $routeEntityPart, 'new'), array(
                '_controller' => 'EasyAdminBundle:Admin:new',
                'entity' => $name,
                'action' => 'new',
            ));
            $collection->add(sprintf($routeNameTemplate, 'new'), $route);
        }

        if (isset($config['edit'])) {
            $route = new Route(sprintf('/%s/%s/{id}', $routeEntityPart, 'edit'), array(
                '_controller' => 'EasyAdminBundle:Admin:edit',
                'entity' => $name,
                'action' => 'edit',
            ));
            $collection->add(sprintf($routeNameTemplate, 'edit'), $route);
        }

        if (true) {
            $route = new Route(sprintf('/%s/%s/{id}', $routeEntityPart, 'delete'), array(
                '_controller' => 'EasyAdminBundle:Admin:delete',
                'entity' => $name,
                'action' => 'delete',
            ));
            $collection->add(sprintf($routeNameTemplate, 'delete'), $route);
        }
    }
}