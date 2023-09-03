<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface FilterDataDtoInterface
{
    public function getEntityAlias(): string;

    public function getProperty(): string;

    public function getFormTypeOption(string $optionName);

    public function getComparison(): string;

    public function getValue(): mixed;

    public function getValue2(): mixed;

    public function getParameterName(): string;

    public function getParameter2Name(): string;
}
