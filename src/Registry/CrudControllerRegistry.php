<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Registry;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudControllerRegistry
{
    /** @var CrudControllerInterface[] */
    private $crudControllers;
    private $controllerToEntityMap;
    private $entityToControllerMap;

    private function __construct()
    {
    }

    public static function new(iterable $crudControllers): self
    {
        $registry = new self();
        $registry->crudControllers = iterator_to_array($crudControllers);

        foreach ($registry->crudControllers as $controller) {
            $controllerFqcn = \get_class($controller);

            try {
                if (null === $entityFqcn = $controller::$entityFqcn) {
                    throw new \RuntimeException();
                }
                $registry->controllerToEntityMap[$controllerFqcn] = $entityFqcn;
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf('The "%s" CRUD controller must define a public static field called "entityFqcn" with the FQCN of the Doctrine entity managed by the controller.', \get_class($controller)));
            }
        }

        // more than one controller can manage the same entity, so this map will
        // only contain the last controller mapped to that repeated entity. That's why
        // several methods in other classes allow to define the CRUD controller explicitly
        $registry->entityToControllerMap = array_flip($registry->controllerToEntityMap);

        return $registry;
    }

    public function getControllerFqcnByEntityFqcn(string $entityFqcn): ?string
    {
        return $this->entityToControllerMap[$entityFqcn] ?? null;
    }

    public function getEntityFqcnByControllerFqcn(string $controllerFqcn): ?string
    {
        return $this->controllerToEntityMap[$controllerFqcn] ?? null;
    }
}
