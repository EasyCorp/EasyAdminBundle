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
class UndefinedConfigurationException extends BaseException
{
    public function __construct(array $parameters = array())
    {
        if (!file_exists($parameters['cachedConfigFilePath'])) {
            $errorMessage = 'The file which stores the processed backend configuration doesn\'t exist.';
            $proposedSolution = 'This file is created automatically. Make sure that the application\'s cache directory is writable and then execute the "cache:clear" command to rebuild it.';
        } else {
            $errorMessage = 'The file which stores the processed backend configuration is malformed.';
            $proposedSolution = 'This file is created automatically. Execute the "cache:clear" command to rebuild it.';
        }

        parent::__construct($errorMessage, $proposedSolution);
    }
}
