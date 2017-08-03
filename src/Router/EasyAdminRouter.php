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

use Doctrine\Common\Util\ClassUtils;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class EasyAdminRouter
{
    /** @var ConfigManager */
    private $configManager;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;
    /** @var RequestStack */
    private $requestStack;
    /** @var Request */
    private $request;

    public function __construct(ConfigManager $configManager, UrlGeneratorInterface $urlGenerator, PropertyAccessorInterface $propertyAccessor, RequestStack $requestStack = null)
    {
        $this->configManager = $configManager;
        $this->urlGenerator = $urlGenerator;
        $this->propertyAccessor = $propertyAccessor;
        $this->requestStack = $requestStack;
    }

    /**
     * @param object|string $entity
     * @param string        $action
     * @param array         $parameters
     *
     * @throws UndefinedEntityException
     *
     * @return string
     */
    public function generate($entity, $action, array $parameters = array())
    {
        if (is_object($entity)) {
            $config = $this->getEntityConfigByClass(get_class($entity));

            // casting to string is needed because entities can use objects as primary keys
            $parameters['id'] = (string) $this->propertyAccessor->getValue($entity, 'id');
        } else {
            $config = class_exists($entity)
                ? $this->getEntityConfigByClass($entity)
                : $this->configManager->getEntityConfig($entity);
        }

        $parameters['entity'] = $config['name'];
        $parameters['action'] = $action;

        $referer = array_key_exists('referer', $parameters) ? $parameters['referer'] : null;
        $request = $this->getRequest();

        if (false === $referer) {
            unset($parameters['referer']);
        } elseif (
            $request
            && !is_string($referer)
            && (true === $referer || in_array($action, array('new', 'edit', 'delete'), true))
        ) {
            $parameters['referer'] = urlencode($request->getUri());
        }

        return $this->urlGenerator->generate('easyadmin', $parameters);
    }

    /**
     * BC for SF < 2.4.
     * To be replaced by the usage of the request stack when 2.3 support is dropped.
     *
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @return Request|null
     */
    private function getRequest()
    {
        if ($this->requestStack && $request = $this->requestStack->getCurrentRequest()) {
            return $request;
        }

        return $this->request;
    }

    /**
     * @param string $class
     *
     * @throws UndefinedEntityException
     *
     * @return array
     */
    private function getEntityConfigByClass($class)
    {
        if (!$config = $this->configManager->getEntityConfigByClass(ClassUtils::getRealClass($class))) {
            throw new UndefinedEntityException(array('entity_name' => $class));
        }

        return $config;
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Router\EasyAdminRouter', 'JavierEguiluz\Bundle\EasyAdminBundle\Router\EasyAdminRouter', false);
