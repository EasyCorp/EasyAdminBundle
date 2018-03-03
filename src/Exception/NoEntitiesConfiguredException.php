<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class NoEntitiesConfiguredException extends BaseException
{
    public function __construct(array $parameters = [])
    {
        $exceptionContext = new ExceptionContext(
            'exception.no_entities_configured',
            'The backend is empty because you haven\'t configured any Doctrine entity to manage. Solution: edit your configuration file (e.g. "config/packages/easy_admin.yaml") and configure the backend under the "easy_admin" key.',
            $parameters,
            500
        );

        parent::__construct($exceptionContext);
    }
}
