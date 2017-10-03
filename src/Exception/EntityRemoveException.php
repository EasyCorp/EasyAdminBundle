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
class EntityRemoveException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        $exceptionContext = new ExceptionContext(
            'exception.entity_remove',
            sprintf('There is a ForeignKeyConstraintViolationException for the Doctrine entity associated with "%s". Solution: disable the "delete" action for this entity or configure the "cascade={"remove"}" attribute for the related property in the Doctrine entity. Full exception: %s', $parameters['entity_name'], $parameters['message']),
            $parameters,
            409
        );

        parent::__construct($exceptionContext);
    }
}

class_alias('EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException', 'JavierEguiluz\Bundle\EasyAdminBundle\Exception\EntityRemoveException', false);
