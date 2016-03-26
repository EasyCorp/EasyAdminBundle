<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Batch\Action;

/**
 * This interface must be implement by all the batch actions.
 *
 * @author unexge <unexge@yandex.com>
 */
interface BatchActionInterface
{
    /**
     * Process the given data with given options.
     *
     * Must return an array with two elements
     *  first one is boolean, the process is success or not
     *  second one is string, the message shows the user
     *
     * @param array|string $data The data variable might be array of ids or string 'all'
     * @param $entity
     * @param $options
     *
     * @return array
     */
    public function process($data, $entity, $options);

    /**
     * Returns boolean, whether this action support the given entity and action or not.
     *
     * @param $entity
     * @param $action
     *
     * @return bool
     */
    public function supports($entity, $action);

    /**
     * Returns the name of the action.
     *
     * @return string
     */
    public function getName();
}
