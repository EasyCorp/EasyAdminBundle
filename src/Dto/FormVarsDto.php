<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FormVarsDto
{
    private FieldDto|null $fieldDto;
    private EntityDto|null $entityDto;

    public function __construct(?FieldDto $fieldDto = null, ?EntityDto $entityDto = null)
    {
        $this->fieldDto = $fieldDto;
        $this->entityDto = $entityDto;
    }

    public function getField(): FieldDto|null
    {
        return $this->fieldDto;
    }

    public function getEntity(): EntityDto|null
    {
        return $this->entityDto;
    }
}
