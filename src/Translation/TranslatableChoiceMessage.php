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
final class TranslatableChoiceMessage implements \Stringable, TranslatableInterface
{
    public function __construct(
        /** @var TranslatableMessage $message */
        private readonly TranslatableInterface $message,
        private readonly ?string $cssClasses,
        private readonly ?string $style = null,
    ) {
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $message = $this->message->trans($translator, $locale);

        return $this->generateHtml($message);
    }

    public function __toString(): string
    {
        return $this->generateHtml($this->message->getMessage());
    }

    private function generateHtml(string $message): string
    {
        if (null !== $this->cssClasses || null !== $this->style) {
            return sprintf(
                '<span %s%s%s>%s</span>',
                null !== $this->cssClasses ? sprintf('class="%s"', $this->cssClasses) : '',
                null !== $this->cssClasses && null !== $this->style ? ' ' : '',
                null !== $this->style ? sprintf('style="%s"', $this->style) : '',
                $message
            );
        }

        return $message;
    }
}
