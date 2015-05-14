<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests;

abstract class CommonPhpUnitTestCase extends \PHPUnit_Framework_TestCase
{
    protected function provideConfigurationFiles($fixturesDir)
    {
        return array_map(null, glob($fixturesDir.'/input/admin_*.yml'), glob($fixturesDir.'/output/config_*.yml'));
    }
}
