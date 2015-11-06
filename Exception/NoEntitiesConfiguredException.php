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
        $templatePath = '@EasyAdmin/error/no_entities.html.twig';
        $errorMessage = "ERROR: your backend is empty because you haven't configured any Doctrine entity to manage.\n\n";
        $errorMessage .= "Solution: open your 'app/config/config.yml' file and configure the backend under the 'easy_admin' key.";

        parent::__construct($errorMessage, $parameters, $templatePath);
    }
}
