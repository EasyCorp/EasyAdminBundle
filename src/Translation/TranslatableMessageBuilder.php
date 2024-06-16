<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Translation;

use function Symfony\Component\Translation\t;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class TranslatableMessageBuilder
{
    /**
     * This method creates a new TranslationMessage object with the same content and domain as the given object,
     * but updates its translation parameters to merge them with the new given parameters.
     *
     * Due to the limited nature of TranslatableInterface we cannot guarantee correct behavior
     * of any other TranslatableInterface implementation, therefore they will be returned as provided.
     */
    public static function withParameters(TranslatableInterface $translatable, array $parameters): TranslatableInterface
    {
        if (!($translatable instanceof TranslatableMessage)) {
            return $translatable;
        }

        return t(
            $translatable->getMessage(),
            array_merge($parameters, $translatable->getParameters()),
            $translatable->getDomain()
        );
    }
}
