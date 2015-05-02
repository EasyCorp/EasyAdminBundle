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
        $fixtures = array();

        $inputs = glob($fixturesDir.'/input/admin_*.yml');
        $outputs = glob($fixturesDir.'/output/config_*.yml');

        $numFixtures = count($inputs);
        for ($i = 0; $i < $numFixtures; $i++) {
            $fixtures[] = array($inputs[$i], $outputs[$i]);
        }

        return $fixtures;
    }
}
