<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Router;

use Doctrine\Common\Util\ClassUtils;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;
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

            $parameters['id'] = $this->propertyAccessor->getValue($entity, 'id');
        } else {
            $config = class_exists($entity)
                ? $this->getEntityConfigByClass($entity)
                : $this->configManager->getEntityConfig($entity);
        }

        $parameters['entity'] = $config['name'];
        $parameters['action'] = $action;

        if ($this->requestStack && !array_key_exists('referer', $parameters)) {
            $request = $this->requestStack->getCurrentRequest();
            $parameters['referer'] = urlencode($request->getUri());
        }

        return $this->urlGenerator->generate('easyadmin', $parameters);
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
