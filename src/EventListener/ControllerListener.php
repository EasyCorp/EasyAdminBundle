<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Sets the right controller to be executed when entities define custom
 * controllers.
 *
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 */
class ControllerListener
{
    private $configManager;
    private $resolver;

    public function __construct(ConfigManager $configManager, ControllerResolverInterface $resolver)
    {
        $this->configManager = $configManager;
        $this->resolver = $resolver;
    }

    /**
     * Exchange default admin controller by custom entity admin controller.
     *
     * @param ControllerEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onKernelController($event)
    {
        $request = $event->getRequest();
        if ('easyadmin' !== $request->attributes->get('_route')) {
            return;
        }

        $currentController = $event->getController();
        // if the controller is defined in a class, $currentController is an array
        // otherwise do nothing because it's a Closure (rare but possible in Symfony)
        if (!\is_array($currentController)) {
            return;
        }

        // this condition happens when accessing the backend homepage, which
        // then redirects to the 'list' action of the first configured entity.
        if (null === $entityName = $request->query->get('entity')) {
            return;
        }

        $entity = $this->configManager->getEntityConfig($entityName);

        // if the entity doesn't define a custom controller, do nothing
        if (!isset($entity['controller'])) {
            return;
        }

        // build the full controller name using the 'class::method' syntax
        $controllerMethod = $currentController[1];
        $customController = $entity['controller'].'::'.$controllerMethod;

        $request->attributes->set('_controller', $customController);
        $newController = $this->resolver->getController($request);

        if (false === $newController) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller for path "%s". Check the "controller" configuration of the "%s" entity in your EasyAdmin backend.', $request->getPathInfo(), $entityName));
        }

        $event->setController($newController);
    }
}
