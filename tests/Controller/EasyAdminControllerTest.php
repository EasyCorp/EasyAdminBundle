<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EasyAdminControllerTest extends TestCase
{
    public function testCall()
    {
        $controller = new EasyAdminController();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('If you are seeing this error, you are probably upgrading your application');
        $controller->index();
    }
}
