<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Registry\AdminControllerRegistry;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Encapsulates CRUD operation-related data for the admin context.
 * Don't use this class directly; use @AdminContext class instead.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudContext
{
    public function __construct(
        private readonly ?CrudDto $crudDto,
        private readonly ?EntityDto $entityDto,
        private readonly ?SearchDto $searchDto,
        private readonly AdminControllerRegistryInterface $adminControllers,
    ) {
    }

    public function getCrud(): ?CrudDto
    {
        return $this->crudDto;
    }

    public function getEntity(): ?EntityDto
    {
        return $this->entityDto;
    }

    public function getSearch(): ?SearchDto
    {
        return $this->searchDto;
    }

    public function getAdminControllers(): AdminControllerRegistryInterface
    {
        return $this->adminControllers;
    }

    /**
     * Creates a CrudContext instance suitable for testing.
     */
    public static function forTesting(
        ?CrudDto $crudDto = null,
        ?EntityDto $entityDto = null,
        ?SearchDto $searchDto = null,
        ?AdminControllerRegistryInterface $adminControllers = null,
    ): self {
        $adminControllers ??= new AdminControllerRegistry(new ArrayAdapter());

        return new self(
            $crudDto ?? new CrudDto(),
            $entityDto,
            $searchDto,
            $adminControllers,
        );
    }
}
