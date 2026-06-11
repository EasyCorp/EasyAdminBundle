<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Fixtures\ChoiceField;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum TranslatableStatusBackedEnum: string implements TranslatableInterface
{
    case Draft = 'draft';
    case Published = 'published';
    case Deleted = 'deleted';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Draft => $translator->trans('TranslatableStatusBackedEnum.draft', locale: $locale),
            self::Published => $translator->trans('TranslatableStatusBackedEnum.published', locale: $locale),
            self::Deleted => $translator->trans('TranslatableStatusBackedEnum.deleted', locale: $locale),
        };
    }
}
