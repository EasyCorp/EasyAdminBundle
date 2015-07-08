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

class InvalidConfigurationException extends BaseException
{
    public function __construct($property, $expected, $given, array $parameters = array())
    {
        $parameters['property'] = $property;

        parent::__construct($parameters);

        $this->setMessage(sprintf(
            "Invalid EasyAdmin configuration.\n".
            "The <code>%s</code> property expected %s.\n".
            "%s given.",
            $property, $expected, $given
        ));
    }
}
