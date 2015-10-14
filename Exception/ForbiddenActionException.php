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

class ForbiddenActionException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        parent::__construct($parameters);

        $message = sprintf("ERROR: the requested '%s' action is not allowed for the '%s' entity.\n\n", $parameters['action'], $parameters['entity']);
        $message .= sprintf("Solution: remove the '%s' action from the 'disabled_actions' option, which can be configured globally for the entire backend or locally for the '%s' entity.\n\n", $parameters['action'], $parameters['entity']);

        $this->setMessage($message);
    }
}
