<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * It stores the variables related to EasyAdmin that are passed to all
 * the form types templates via the `form.vars.ea_vars` variable. It's a similar
 * concept to the variables passed by Symfony via the `form.vars` variable.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class FormVarsDto
{
    public function __construct(private ?FieldDto $fieldDto = null, private ?EntityDto $entityDto = null)
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
