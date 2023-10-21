<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * It stores the variables related to EasyAdmin that are passed to all
 * the form types templates via the `form.vars.ea_vars` variable. It's a similar
 * concept to the variables passed by Symfony via the `form.vars` variable.
 *
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
