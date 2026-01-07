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
    public function __construct(private readonly ?FieldDto $fieldDto = null, private readonly ?EntityDto $entityDto = null)
    {
    }

    public function getField(): ?FieldDto
    {
        return $this->fieldDto;
    }

    public function getEntity(): ?EntityDto
    {
        return $this->entityDto;
    }
}
