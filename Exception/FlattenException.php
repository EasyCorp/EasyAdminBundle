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

use Symfony\Component\Debug\Exception\FlattenException as BaseFlattenException;

/**
 * @author Maxime Steinhausser <maxime.steinhausser@gmail.com>
 */
class FlattenException extends BaseFlattenException
{
    public static function create(\Exception $exception, $statusCode = null, array $headers = array())
    {
        if (!$exception instanceof BaseException) {
            throw new \RuntimeException(sprintf(
                'You should only try to create an instance of "%s" with a "JavierEguiluz\Bundle\EasyAdminBundle\Exception\BaseException" instance, or subclass. "%s" given.',
                __CLASS__,
                get_class($exception)
            ));
        }

        /** @var FlattenException $e */
        $e = parent::create($exception, $statusCode, $headers);
        $e->setStatusCode($exception->getStatusCode());

        return $e;
    }
}
