<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

use EasyCorp\Bundle\EasyAdminBundle\Context\ExceptionContext;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class EntityNotFoundException extends BaseException
{
    public function __construct(array $parameters = [])
    {
        $exceptionContext = new ExceptionContext(
            'exception.entity_not_found',
            sprintf('The "%s" entity with "%s = %s" does not exist in the database. The entity may have been deleted by mistake or by a "cascade={"remove"}" operation executed by Doctrine.', $parameters['entity_name'], $parameters['entity_id_name'], $parameters['entity_id_value']),
            $parameters,
            404
        );

        parent::__construct($exceptionContext);
    }
}
