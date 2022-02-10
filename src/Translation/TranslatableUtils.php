<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Translation;

use function Symfony\Component\Translation\t;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class TranslatableUtils
{
    /**
     * This method will append provided parameters to translatable object if it is of TranslatableMessage class.
     *
     * Due to the limited nature of TranslatableInterface we cannot guarantee correct behavior
     * of any other TranslatableInterface implementation, therefore they will be returned as provided.
     */
    public static function withParameters(TranslatableInterface $translatable, array $parameters): TranslatableInterface
    {
        if (TranslatableMessage::class !== \get_class($translatable)) {
            return $translatable;
        }

        return t(
            $translatable->getMessage(),
            array_merge(
                $parameters,
                $translatable->getParameters()
            ),
            $translatable->getDomain()
        );
    }
}
