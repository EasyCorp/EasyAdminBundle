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

class EntityNotFoundException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        $parameters['message'] = sprintf(
            'The <code>%s</code> entity with <code>%s = %s</code> does not exist in the database.',
            $parameters['entity']['name'],
            $parameters['entity']['primary_key_field_name'],
            $parameters['entity_id']
        );

        parent::__construct($parameters);
    }
}
