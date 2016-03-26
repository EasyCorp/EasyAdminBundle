<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\DependencyInjection\Batch;

use JavierEguiluz\Bundle\EasyAdminBundle\Batch\Action\BatchActionInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Batch\BatchActionManager;
use Prophecy\Argument;

class BatchActionManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BatchActionManager
     */
    protected $manager;

    /**
     * @var BatchActionInterface
     */
    protected $actionOne;

    /**
     * @var BatchActionInterface
     */
    protected $actionTwo;


    public function setUp()
    {
        $manager = new BatchActionManager();

        $actionOne = $this->prophesize(BatchActionInterface::class);
        $actionOne->supports(Argument::any(), Argument::type('string'))->will(function($args) {
            return 'list' === $args[1];
        });
        $actionOne->getName()->willReturn('action_one');
        $actionOne->process(Argument::type('array'), Argument::any(), Argument::type('array'))->willReturn(array(true, 'Success!'));

        $manager->addAction($this->actionOne = $actionOne->reveal());

        $actionTwo = $this->prophesize(BatchActionInterface::class);
        $actionTwo->supports(Argument::any(), Argument::type('string'))->willReturn(false);
        $actionTwo->getName()->willReturn('action_two');

        $manager->addAction($this->actionTwo = $actionTwo->reveal());

        $this->manager = $manager;
    }

    public function testGetAction()
    {
        $this->assertEquals($this->actionOne, $this->manager->getAction('action_one'));
        $this->assertEquals($this->actionTwo, $this->manager->getAction('action_two'));
    }

    public function testGetSupportedActions()
    {
        $this->assertEquals(
            array(
                'action_one' => $this->actionOne
            ),
            $this->manager->getSupportedActions(null, 'list')
        );
    }

    public function testReturnEmptyArrayIfNoneOfTheActionsSupportsTheCondition()
    {
        $this->assertEquals(array(), $this->manager->getSupportedActions(null, 'search'));
    }

    /**
     * @expectedException \JavierEguiluz\Bundle\EasyAdminBundle\Exception\InvalidBatchActionException
     */
    public function testFailToTryingGetInvalidAction()
    {
        $this->manager->getAction('not_exists');
    }

    public function testProcessAction()
    {
        $this->assertEquals(
            array(true, 'Success!'),
            $this->manager->processAction('action_one', array(50, 57), null, array())
        );
    }
}
