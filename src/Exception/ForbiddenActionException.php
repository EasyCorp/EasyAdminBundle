<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContext;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ForbiddenActionException extends BaseException
{
    public function __construct(AdminContext $adminContext)
    {
        $parameters = [
            'action' => $adminContext->getCrud()->getCurrentAction(),
            'crud_controller' => $adminContext->getRequest()->query->get('crudController'),
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
