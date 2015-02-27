<?php


namespace JavierEguiluz\Bundle\EasyAdminBundle\Listener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Forces the locale used to translate the EasyAdmin interface on EasyAdmin routes.
 *
 * @package JavierEguiluz\Bundle\EasyAdminBundle\Listener
 */
class ForceLocaleListener implements EventSubscriberInterface
{
    private $forcedLocale;

    public function __construct($forcedLocale = 'en')
    {
        $this->forcedLocale = $forcedLocale;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if('admin' === $request->get('_route')) {
            $request->setLocale($this->forcedLocale);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 14)),
        );
    }
}
