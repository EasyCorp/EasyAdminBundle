<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as EasyAdminController;

class CustomController extends EasyAdminController
{
    /** Empty method defined to trigger a deprecation message */
    public function prePersistCategory2Entity($entity)
    {
        parent::prePersistEntity($entity);
    }

    /** Empty method defined to trigger a deprecation message */
    public function preUpdateCategory2Entity($entity)
    {
        parent::preUpdateEntity($entity);
    }

    /** Empty method defined to trigger a deprecation message */
    public function preRemoveCategory2Entity($entity)
    {
        parent::preUpdateEntity($entity);
    }
}
