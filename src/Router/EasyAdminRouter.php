<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Router;

use Doctrine\Persistence\Proxy;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use EasyCorp\Bundle\EasyAdminBundle\Exception\UndefinedEntityException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Konstantin Grachev <me@grachevko.ru>
 */
final class EasyAdminRouter
{
    private $configManager;
    private $urlGenerator;
    private $propertyAccessor;
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
    public function generate($entity, $action, array $parameters = [])
    {
        if (\is_object($entity)) {
            $config = $this->getEntityConfigByClass(\get_class($entity));

            // casting to string is needed because entities can use objects as primary keys
            $parameters['id'] = (string) $this->propertyAccessor->getValue($entity, 'id');
        } else {
            $config = class_exists($entity)
                ? $this->getEntityConfigByClass($entity)
                : $this->configManager->getEntityConfig($entity);
        }

        $parameters['entity'] = $config['name'];
        $parameters['action'] = $action;

        $referer = $parameters['referer'] ?? null;

        $request = null;
        if (null !== $this->requestStack) {
            $request = $this->requestStack->getCurrentRequest();
        }

        if (false === $referer) {
            unset($parameters['referer']);
        } elseif (
            $request
            && !\is_string($referer)
            && (true === $referer || \in_array($action, ['new', 'edit', 'delete'], true))
        ) {
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
        if (!$config = $this->configManager->getEntityConfigByClass($this->getRealClass($class))) {
            throw new UndefinedEntityException(['entity_name' => $class]);
        }

        return $config;
    }

    /**
     * @param string $class
     *
     * @return string
     */
    private function getRealClass($class)
    {
        if (false === $pos = strrpos($class, '\\'.Proxy::MARKER.'\\')) {
            return $class;
        }

        return substr($class, $pos + Proxy::MARKER_LENGTH + 2);
    }
}
