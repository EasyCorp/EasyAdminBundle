<?php

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests;

class CommonPhpUnitTestCase extends \PHPUnit_Framework_TestCase
{
    public function provideConfigurationFiles($fixturesDir)
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
