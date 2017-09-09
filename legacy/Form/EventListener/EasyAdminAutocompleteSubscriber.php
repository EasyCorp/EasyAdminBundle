<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

@trigger_error('The '.__NAMESPACE__.'\EasyAdminAutocompleteSubscriber class is deprecated since version 1.16 and will be removed in 2.0. Use the EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminAutocompleteSubscriber class instead.', E_USER_DEPRECATED);

class_exists('EasyCorp\Bundle\EasyAdminBundle\Form\EventListener\EasyAdminAutocompleteSubscriber');

if (\false) {
    class EasyAdminAutocompleteSubscriber implements EventSubscriberInterface
    {
    }
}
