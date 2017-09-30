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
class UndefinedEntityException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        $exceptionContext = new ExceptionContext(
            'exception.undefined_entity',
            sprintf('The "%s" entity is not defined in the configuration of your backend. Solution: edit your configuration file (e.g. "app/config/config.yml") and add the "%s" entity to the list of entities managed by EasyAdmin.', $parameters['entity_name'], $parameters['entity_name']),
            $parameters,
            404
        );

        parent::__construct($exceptionContext);
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Exception\UndefinedEntityException', 'JavierEguiluz\Bundle\EasyAdminBundle\Exception\UndefinedEntityException', false);
