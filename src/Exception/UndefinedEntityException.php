<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class UndefinedEntityException extends BaseException
{
    public function __construct(array $parameters = [])
    {
        $exceptionContext = new ExceptionContext(
            'exception.undefined_entity',
            sprintf('The "%s" entity is not defined in the configuration of your backend. Solution: edit your configuration file (e.g. "config/packages/easy_admin.yaml") and add the "%s" entity to the list of entities managed by EasyAdmin.', $parameters['entity_name'], $parameters['entity_name']),
            $parameters,
            404
        );

        parent::__construct($exceptionContext);
    }
}
