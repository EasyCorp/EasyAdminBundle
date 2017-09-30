<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Exception;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class NoEntitiesConfiguredException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        $exceptionContext = new ExceptionContext(
            'exception.no_entities_configured',
            'The backend is empty because you haven\'t configured any Doctrine entity to manage. Solution: edit your configuration file (e.g. "app/config/config.yml") and configure the backend under the "easy_admin" key.',
            $parameters,
            500
        );

        parent::__construct($exceptionContext);
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Exception\NoEntitiesConfiguredException', 'JavierEguiluz\Bundle\EasyAdminBundle\Exception\NoEntitiesConfiguredException', false);
