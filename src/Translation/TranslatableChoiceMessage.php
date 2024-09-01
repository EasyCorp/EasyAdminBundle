<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Translation;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @internal
 */
final class TranslatableChoiceMessage implements TranslatableInterface
{
    /**
     * @param TranslatableMessage $message
     */
    public function __construct(
        private TranslatableInterface $message,
        private ?string $cssClass,
    ) {
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $message = $this->message->trans($translator, $locale);

        if (null !== $this->cssClass) {
            return sprintf('<span class="%s">%s</span>', $this->cssClass, $message);
        }

        return $message;
    }

    public function __toString(): string
    {
        if (null !== $this->cssClass) {
            return sprintf('<span class="%s">%s</span>', $this->cssClass, $this->message);
        }

        return (string) $this->message;
    }
}
