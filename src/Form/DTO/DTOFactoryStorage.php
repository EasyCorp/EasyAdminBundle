<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DTO;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;

final class DTOFactoryStorage
{
    private $configManager;

    /**
     * @var DTOFactoryInterface[]
     */
    private $factories = [];

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function addFactory(string $serviceName, DTOFactoryInterface $objectFactory): void
    {
        $this->factories[$serviceName] = $objectFactory;
    }

    public function hasFactory(?string $serviceName): bool
    {
        return isset($this->factories[$serviceName]);
    }

    public function createEntityDTO(string $entityName, string $view, $entityObject = null)
    {
        $entityConfig = $this->configManager->getEntityConfig($entityName);

        $dtoClass = $entityConfig[$view]['dto_class'];
        $factory = $entityConfig[$view]['dto_factory'];

        if ($this->hasFactory($factory)) {
            return $this->factories[$factory]->createDTO($dtoClass, $view, $entityObject);
        }

        if (null === $factory || '__construct' === $factory) {
            return new $dtoClass($entityObject);
        }

        if (false === \strpos($factory, '::')) {
            $callable = $dtoClass.'::'.$factory;

            return $callable($entityObject);
        }

        if (\is_callable($factory)) {
            // Static factories
            return $factory($entityObject);
        }

        throw new \InvalidArgumentException(\sprintf(
        'Could not find a way to create a DTO for entity %s with configured factory %s.',
            $entityName, $factory
        ));
    }
}
