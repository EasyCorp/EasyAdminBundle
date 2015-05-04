<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AbstractTestCase. Code copied from
 * https://github.com/Orbitale/CmsBundle/blob/master/Tests/Fixtures/AbstractTestCase.php
 * (c) Alexandre Rock Ancelet <alex@orbitale.io>
 */
abstract class AbstractTestCase extends WebTestCase
{
    /**
     * @var ContainerInterface
     */
    protected static $container;

    /**
     * @param array $options
     */
    protected static function bootKernel(array $options = array())
    {
        if (method_exists('Symfony\Bundle\FrameworkBundle\Test\KernelTestCase', 'bootKernel')) {
            parent::bootKernel($options);
        } else {
            if (null !== static::$kernel) {
                static::$kernel->shutdown();
            }
            static::$kernel = static::createKernel($options);
            static::$kernel->boot();
            static::$kernel;
        }
    }

    /**
     * @param array $options An array of options to pass to the createKernel class
     * @return KernelInterface
     */
    protected function getKernel(array $options = array())
    {
        static::bootKernel($options);
        return static::$kernel;
    }

}
