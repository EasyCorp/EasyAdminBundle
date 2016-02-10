<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Batch;

use JavierEguiluz\Bundle\EasyAdminBundle\Batch\Action\BatchActionInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\InvalidBatchActionException;

/**
 * Manages all the batch actions.
 *
 * @author unexge <unexge@yandex.com>
 */
class BatchActionManager
{
    /**
     * Holds action instances.
     *
     * @var BatchActionInterface[]
     */
    private $actions = array();


    /**
     * Adds new action.
     *
     * @param BatchActionInterface $action
     */
    public function addAction(BatchActionInterface $action)
    {
        $this->actions[$action->getName()] = $action;
    }

    /**
     * Gets an action.
     *
     * @param $name
     *
     * @throws InvalidBatchActionException if the action does not exists.
     *
     * @return BatchActionInterface
     */
    public function getAction($name)
    {
        if ( ! isset($this->actions[$name])) {
            throw new InvalidBatchActionException($name);
        }

        return $this->actions[$name];
    }

    /**
     * Returns array of actions, that supports given entity and action.
     *
     * @param        $entity
     * @param string $action
     *
     * @return BatchActionInterface[]
     */
    public function getSupportedActions($entity, $action = 'list')
    {
        return array_filter($this->actions, function(BatchActionInterface $batchAction) use ($entity, $action) {
            return $batchAction->supports($entity, $action);
        });
    }

    /**
     * Process given action and return its response.
     *
     * @param       $name
     * @param       $data
     * @param       $entity
     * @param array $options
     *
     * @return array
     */
    public function processAction($name, $data, $entity, $options = array())
    {
        return $this->getAction($name)->process($data, $entity, $options);
    }
}
