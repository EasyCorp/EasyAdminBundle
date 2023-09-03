<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface LocaleInterface
{
    public function getAsDto(): LocaleDtoInterface;

    public static function new(string $locale, string|null $label = null, ?string $icon = null): self;
}
