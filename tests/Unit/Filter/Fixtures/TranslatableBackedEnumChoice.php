<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter\Fixtures;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TranslatableBackedEnumChoice: string implements TranslatableInterface
{
    case Draft = 'draft';
    case Published = 'published';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Draft => $translator->trans('TranslatableBackedEnumChoice.draft', locale: $locale),
            self::Published => $translator->trans('TranslatableBackedEnumChoice.published', locale: $locale),
        };
    }
}
