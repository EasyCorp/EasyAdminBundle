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
        parent::__construct($parameters);

        $this->setTemplatePath('@EasyAdmin/error/undefined_entity.html.twig');
        $this->setHttpStatusCode(500);

        $message = sprintf("ERROR: the '%s' entity is not defined in the configuration of your backend.\n\n", $parameters['entity_name']);
        $message .= sprintf("Solution: open your 'app/config/config.yml' file and add the '%s' entity to the list of entities managed by EasyAdmin.\n\n", $parameters['entity_name']);

        $this->setMessage($message);
    }
}
