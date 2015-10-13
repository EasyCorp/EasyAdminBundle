<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JavierEguiluz\Bundle\EasyAdminBundle\Tests\Controller;

use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AbstractTestCase;
use JavierEguiluz\Bundle\EasyAdminBundle\Tests\Fixtures\AppTestBundle\Controller\AdminController;
use Symfony\Component\HttpFoundation\Request;

class CustomFieldFormTypeTest extends AbstractTestCase
{
    /**
     * @var AdminController
     */
    protected $controller;

    public function setUp()
    {
        // Only here to avoid the `initClient` method to be used.
        // It's useless in this test.
    }

    /**
     * @param array $options
     *
     * @return AdminController
     */
    public function getController(array $options = array())
    {
        static::bootKernel($options);
        $controller = new AdminController();
        $controller->setContainer(static::$kernel->getContainer());
        return $controller;
    }

    public function testFormTypeIsString()
    {
        $controller = static::getController(array('environment' => 'form_type_checker'));
        // Simulates the "new" action
        $controller->initialize(new Request(array('entity' => 'Customer', 'action' => 'new')));
        $entity = $controller->findCurrentEntity();
        $fields = $controller->entity['new']['fields'];

        $builder = $controller->createEntityFormBuilder($entity, $fields, 'new');

        // TODO
    }
}
