<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Router;

use Doctrine\Common\Util\ClassUtils;
use JavierEguiluz\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class EasyAdminEntityRouter
{
    /** @var ConfigManager */
    private $configManager;
    /** @var RouterInterface */
    private $router;
    /** @var RequestStack */
    private $requestStack;
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(ConfigManager $configManager, RouterInterface $router, RequestStack $requestStack, PropertyAccessorInterface $propertyAccessor) {
        $this->configManager = $configManager;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object|string $entity
     * @param array         $parameters
     *
     * @throws UndefinedEntityException
     *
     * @return string
     */
    public function generate($entity, array $parameters = array())
    {
        if (is_object($entity)) {
            $config = $this->getEntityConfigByClass(get_class($entity));

            $params = array(
                'id' => $this->propertyAccessor->getValue($entity, 'id'),
                'action' => 'edit',
            );
        } else {
            $config = class_exists($entity)
                ? $this->getEntityConfigByClass($entity)
                : $this->configManager->getEntityConfig($entity);

            $params = array(
                'action' => 'new',
            );
        }

        $request = $this->requestStack->getMasterRequest();

        $params['entity'] = $config['name'];
        $params['referer'] = urlencode($request->getUri());

        return $this->router->generate('easyadmin', array_merge($params, $parameters));
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
