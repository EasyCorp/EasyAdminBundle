<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class NoPermissionException extends BaseException
{
    public function __construct(array $parameters = [])
    {
        if (null !== $parameters['entity_id']) {
            $privateMessage = sprintf('The logged in user does not have the required roles to complete the "%s" action on the "%s" entity with ID "%s".', $parameters['action'], $parameters['entity_name'], $parameters['entity_id']);
        } else {
            $privateMessage = sprintf('The logged in user does not have the required roles to complete the "%s" action on the "%s" entity.', $parameters['action'], $parameters['entity_name']);
        }

        $exceptionContext = new ExceptionContext('exception.no_permission', $privateMessage, $parameters, 401);

        parent::__construct($exceptionContext);
    }
}
