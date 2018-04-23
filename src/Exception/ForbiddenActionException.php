<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ForbiddenActionException extends BaseException
{
    public function __construct(array $parameters = [])
    {
        $exceptionContext = new ExceptionContext(
            'exception.forbidden_action',
            sprintf('The requested "%s" action is not allowed for the "%s" entity. Solution: remove the "%s" action from the "disabled_actions" option, which can be configured globally for the entire backend or locally for the "%s" entity.', $parameters['action'], $parameters['entity_name'], $parameters['action'], $parameters['entity_name']),
            $parameters,
            403
        );

        parent::__construct($exceptionContext);
    }
}
