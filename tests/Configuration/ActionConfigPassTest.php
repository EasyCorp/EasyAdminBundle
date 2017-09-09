<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\DependencyInjection\Compiler;

use EasyCorp\Bundle\EasyAdminBundle\Configuration\ActionConfigPass;

class ActionConfigPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getWrongActionConfigs
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage One of the actions defined by the global "list" view defined under "easy_admin" option contains an invalid value (action config can only be a YAML string or hash).
     */
    public function testActionconfigFormat($actionsConfig)
    {
        $configPass = new ActionConfigPass();
        $method = new \ReflectionMethod($configPass, 'doNormalizeActionsConfig');
        $method->setAccessible(true);

        $method->invoke($configPass, $actionsConfig, 'the global "list" view defined under "easy_admin" option');
    }

    public function getWrongActionConfigs()
    {
        return array(
            array(array(7)),
            array(array(true)),
            array(array(null)),
        );
    }
}
