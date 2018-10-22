<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DTO;

interface DTOEntityCallable
{
    /**
     * If anything is returned but null, this will replace the entity
     * that will be persisted in the AdminController.
     *
     * @param object $dto An instance of the DTO configured in the "dto_class" option.
     * @param object|null $entity "null" for "new" action, and the actual entity for "edit" action.
     * @param string $action Either "edit" or "new".
     *
     * @return object|null The new entity or null.
     */
    public function updateEntity($dto, $entity, string $action);
}
