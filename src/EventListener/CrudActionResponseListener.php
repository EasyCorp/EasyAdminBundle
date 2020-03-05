<?php

namespace EasyCorp\Bundle\EasyAdminBundle\EventListener;

use EasyCorp\Bundle\EasyAdminBundle\Context\ApplicationContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Controller\ResponseParameters;
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
        $responseParameters = $event->getControllerResult();
        if (null === $responseParameters || !$responseParameters instanceof ResponseParameters) {
            return;
        }

        if (!$responseParameters->has('templateName') && !$responseParameters->has('templatePath')) {
            throw new \RuntimeException(sprintf('The ResponseParameters object returned by CrudController actions must include either a "templateName" or a "templatePath" parameter to define the template used to render the action result.'));
        }

        $templateParameters = $responseParameters->all();
        $templatePath = \array_key_exists('templatePath', $templateParameters)
            ? $templateParameters['templatePath']
            : $this->applicationContextProvider->getContext()->getTemplatePath($templateParameters['templateName']);

        // to make parameters easier to modify, we pass around FormInterface objects
        // so we must convert those values to FormView before rendering the template
        foreach ($templateParameters as $paramName => $paramValue) {
            if ($paramValue instanceof FormInterface) {
                $templateParameters[$paramName] = $paramValue->createView();
            }
        }

        $event->setResponse(new Response($this->twig->render($templatePath, $templateParameters)));
    }
}
