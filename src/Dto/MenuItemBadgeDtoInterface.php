<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemBadgeDtoInterface
{
    public function getContent(): mixed;

    public function getCssClass(): string;

    public function getHtmlStyle(): string;
}
