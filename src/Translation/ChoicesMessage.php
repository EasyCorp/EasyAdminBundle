<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Translation;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @internal
 */
final class ChoicesMessage implements TranslatableInterface
{
    public function __construct(
        /** @var ChoiceMessage[] */
        private array $choices,
        private bool $isRenderedAsBadge
    ) {
    }

    public function trans(TranslatorInterface $translator, string $locale = null): string
    {
        return implode(
            $this->isRenderedAsBadge ? '' : ', ',
            array_map(
                static fn (ChoiceMessage $message) => $message->trans($translator, $locale),
                $this->choices
            )
        );
    }

    public function __toString(): string
    {
        return implode(
            $this->isRenderedAsBadge ? '' : ', ',
            $this->choices
        );
    }
}
