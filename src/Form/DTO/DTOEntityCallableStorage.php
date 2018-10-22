<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DTO;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;

/**
 * This service only stores callables that are defined through the "dto_entity_callable" option.
 * It is used to avoid users to define their services as public to use their callables.
 */
final class DTOEntityCallableStorage
{
    /**
     * @var DTOEntityCallable[]
     */
    private $callables = [];
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function addCallable(string $serviceName, DTOEntityCallable $mapper): void
    {
        $this->callables[$serviceName] = $mapper;
    }

    public function hasCallable(string $serviceName): bool
    {
        return isset($this->callables[$serviceName]);
    }

    /**
     * @return null|callable|DTOEntityCallable
     */
    public function getCallable(string $entityName, string $view)
    {
        $entityConfig = $this->configManager->getEntityConfig($entityName);

        $callable = $entityConfig[$view]['dto_entity_callable'];

        if ($this->hasCallable($callable)) {
            return [$this->callables[$callable], 'updateEntity'];
        }

        if (\is_callable($callable)) {
            return $callable;
        }

        if (!\is_array($callable)) {
            // Callable must be a string at this point.
            if (false !== \strpos('::', $callable)) {
                // dto_entity_callable: anyEntityMethod
                // Should resolve as $entity->$callable
                return [$entityConfig['class'], $callable];
            }

            $callable = \explode('::', $callable);
        }

        // Entity should be an array at this point
        [$callableClass, $method] = $callable;

        if ($this->hasCallable($callableClass)) {
            // dto_entity_callable: App\MyCallable::anotherMethod
            return [$this->callables[$callableClass], $method];
        }

        throw new \InvalidArgumentException(sprintf(
            'Could not find a DTO-to-entity callable for entity %s with configured DTO callable %s.',
            $entityName, $callable
        ));
    }
}
