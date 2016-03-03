<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Routing\Generator;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Url generator class for EasyAdmin route based.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class UrlGenerator
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Generates a path for a specific EasyAdmin route based on the given entity, action and parameters.
     *
     * @param string $entity        The name of the entity route
     * @param string $action        The name of the action route
     * @param array  $parameters    An array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string The generated URL
     */
    public function generate($entity, $action, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $name = sprintf('easyadmin_%s_%s', strtolower($entity), strtolower($action));

        return $this->router->generate($name, $parameters, $referenceType);
    }
}
