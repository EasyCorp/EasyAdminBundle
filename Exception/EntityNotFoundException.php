<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Exception;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class EntityNotFoundException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        parent::__construct($parameters);

        $message = sprintf("ERROR: the '%s' entity with '%s = %s' does not exist in the database.\n\n", $parameters['entity']['name'], $parameters['entity']['primary_key_field_name'], $parameters['entity_id']);

        $this->setMessage($message);
    }
}
