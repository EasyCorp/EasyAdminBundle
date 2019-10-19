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
    public function supports(ParamConverter $configuration)
    {
        // TODO
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        // TODO
    }
}
