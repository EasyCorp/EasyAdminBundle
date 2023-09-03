<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface BatchActionDtoInterface
{
    public function getName(): string;

    public function getEntityIds(): array;

    public function getEntityFqcn(): string;

    public function getReferrerUrl(): string;

    public function getCsrfToken(): string;
}
