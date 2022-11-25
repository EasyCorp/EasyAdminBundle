<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContext;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class InsufficientEntityPermissionException extends BaseException
{
    public function __construct(AdminContext $adminContext)
    {
        $parameters = [
            'entity_fqcn' => $adminContext->getEntity()->getFqcn(),
            'entity_id' => $entityId = $adminContext->getRequest()->query->get('entityId'),
        ];

        if (null !== $entityId) {
            $debugMessage = sprintf('You don\'t have enough permissions to access this instance of the "%s" entity.', $parameters['entity_fqcn']);
        } else {
            $debugMessage = sprintf('You don\'t have enough permissions to access the instance of the "%s" entity with id  = %s.', $parameters['entity_fqcn'], $entityId);
        }

        $exceptionContext = new ExceptionContext(
            'exception.insufficient_entity_permission',
            $debugMessage,
            $parameters,
            403
        );

        parent::__construct($exceptionContext);
    }
}
