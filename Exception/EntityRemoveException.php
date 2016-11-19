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
class EntityRemoveException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        $errorMessage = sprintf('You can\'t delete this "%s" item because other items depend on it in the database.', $parameters['entity']);
        $proposedSolution = "Don't delete this item or change the database configuration to allow deleting it.";

        parent::__construct($errorMessage, $proposedSolution, 404);
    }
}
