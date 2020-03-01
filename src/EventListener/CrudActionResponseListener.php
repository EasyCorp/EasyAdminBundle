<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParams;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Twig\Environment;

final class CrudActionResponseListener
{
    private $applicationContextProvider;
    private $twig;

    public function __construct(ApplicationContextProvider $applicationContextProvider, Environment $twig)
    {
        $this->applicationContextProvider = $applicationContextProvider;
        $this->twig = $twig;
    }

    public function onKernelView(ViewEvent $event)
    {
        $responseParams = $event->getControllerResult();
        if (null === $responseParams || !$responseParams instanceof ResponseParams) {
            return;
        }

        if (!$responseParams->has('templateName')) {
            throw new \RuntimeException(sprintf('The params returned by CrudController actions must include a param called "templateParam" with the template used to render the action result.'));
        }

        $templateParams = $responseParams->all();
        $templatePath = $this->applicationContextProvider->getContext()->getTemplatePath($templateParams['templateName']);

        // to make params easier to modify, we pass around FormInterface objects
        // so we must convert those values to FormView before rendering the template
        foreach ($templateParams as $paramName => $paramValue) {
            if ($paramValue instanceof FormInterface) {
                $templateParams[$paramName] = $paramValue->createView();
            }
        }

        $event->setResponse(new Response($this->twig->render($templatePath, $templateParams)));
    }
}
