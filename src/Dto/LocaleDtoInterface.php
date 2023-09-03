<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
interface LocaleDtoInterface
{
    public function getLocale(): string;

    public function getName(): string;

    public function getIcon(): ?string;
}
