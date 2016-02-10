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
 * @author unexge <unexge@yandex.com>
 */
class InvalidBatchActionException extends BaseException
{
    public function __construct($name)
    {
        $errorMessage = sprintf('The batch action named "%s" does not exists.', $name);
        $proposedSolution = sprintf('Make sure the defined "%s".', $name);

        parent::__construct($errorMessage, $proposedSolution, 404);
    }
}
