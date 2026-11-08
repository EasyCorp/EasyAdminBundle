<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum BlogPostStateEnum: string implements TranslatableInterface
{
    case Draft = 'draft';
    case Published = 'published';
    case Deleted = 'deleted';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Draft => $translator->trans('BlogPostStateEnum.draft', locale: $locale),
            self::Published => $translator->trans('BlogPostStateEnum.published', locale: $locale),
            self::Deleted => $translator->trans('BlogPostStateEnum.deleted', locale: $locale),
        };
    }
}
