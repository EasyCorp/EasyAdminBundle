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
class UndefinedEntityException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        $errorMessage = sprintf('The "%s" entity is not defined in the configuration of your backend.', $parameters['entity_name']);
        $proposedSolution = sprintf('Open your "app/config/config.yml" file and add the "%s" entity to the list of entities managed by EasyAdmin.', $parameters['entity_name']);

        parent::__construct($errorMessage, $proposedSolution);
    }
}
