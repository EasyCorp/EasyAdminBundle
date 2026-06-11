<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TranslatableUnitEnumChoice implements TranslatableInterface
{
    case Draft;
    case Published;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Draft => $translator->trans('TranslatableUnitEnumChoice.draft', locale: $locale),
            self::Published => $translator->trans('TranslatableUnitEnumChoice.published', locale: $locale),
        };
    }
}
