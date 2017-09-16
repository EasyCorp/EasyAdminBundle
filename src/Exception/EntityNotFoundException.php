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
class EntityNotFoundException extends BaseException
{
    public function __construct(array $parameters = array())
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

class_alias('EasyCorp\Bundle\EasyAdminBundle\Exception\EntityNotFoundException', 'JavierEguiluz\Bundle\EasyAdminBundle\Exception\EntityNotFoundException', false);
