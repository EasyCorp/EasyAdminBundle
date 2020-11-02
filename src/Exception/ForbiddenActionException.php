<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContext;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ForbiddenActionException extends BaseException
{
    public function __construct(?AdminContext $context = null)
    {
        if (null === $context) {
            parent::__construct(new ExceptionContext('exception.forbidden_action', sprintf('You can\'t run this action.'), [], 403));

            return;
        }

        $parameters = [
            'crud_controller' => null === $context->getCrud() ? null : $context->getCrud()->getControllerFqcn(),
            'action' => null === $context->getCrud() ? null : $context->getCrud()->getCurrentAction(),
        ];

        $exceptionContext = new ExceptionContext(
            'exception.forbidden_action',
            sprintf('You don\'t have enough permissions to run the "%s" action on the "%s" or the "%s" action has been disabled.', $parameters['action'], $parameters['crud_controller'], $parameters['action']),
            $parameters,
            403
        );

        parent::__construct($exceptionContext);
    }
}
