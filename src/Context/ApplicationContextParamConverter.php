<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A param converter that injects the current application context object when
 * an argument is type-hinted with ApplicationContext class.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ApplicationContextParamConverter implements ParamConverterInterface
{
    private $applicationContextProvider;

    public function __construct(ApplicationContextProvider $applicationContextProvider)
    {
        $this->applicationContextProvider = $applicationContextProvider;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === ApplicationContext::class;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $request->attributes->set($configuration->getName(), $this->applicationContextProvider->getContext());
    }
}
