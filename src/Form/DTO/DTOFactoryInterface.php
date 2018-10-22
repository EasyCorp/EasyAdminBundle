<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Form\DTO;

interface DTOFactoryInterface
{
    public function createDTO(string $dtoClass, string $view, $defaultData = null);
}
