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
class NoEntitiesConfiguredException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        $errorMessage = 'Your backend is empty because you haven\'t configured any Doctrine entity to manage.';
        $proposedSolution = 'Open your "app/config/config.yml" file and configure the backend under the "easy_admin" key.';

        parent::__construct($errorMessage, $proposedSolution);
    }
}
