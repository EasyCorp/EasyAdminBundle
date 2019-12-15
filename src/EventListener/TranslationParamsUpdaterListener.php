<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityBuiltEvent;

final class TranslationParamsUpdaterListener
{
    private $applicationContextProvider;

    public function __construct(ApplicationContextProvider $applicationContextProvider)
    {
        $this->applicationContextProvider = $applicationContextProvider;
    }

    public function updateTranslationParams(AfterEntityBuiltEvent $event): void
    {
        // in the 'index' page there are multiple entities instead of just one,
        // so it doesn't make sense to create these translation parameters
        if ('index' === $this->applicationContextProvider->getContext()->getCrud()->getAction()) {
            return;
        }

        $entityDto = $event->getEntity();
        $i18nDto = $this->applicationContextProvider->getContext()->getI18n();

        $i18nDto->addTranslationParam('%entity_name%', $entityDto->getName());

        if (null !== $idValue = $entityDto->getIdValue()) {
            $i18nDto->addTranslationParam('%entity_id%', $idValue);
        }
    }
}
