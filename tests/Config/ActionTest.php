<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use PHPUnit\Framework\TestCase;

class ActionTest extends TestCase
{
    public function testDefaultCssClass()
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('');

        $this->assertSame('', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public function testSetCssClass()
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('')
            ->setCssClass('foo');

        $this->assertSame('foo', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public function testAddCssClass()
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('')
            ->addCssClass('foo');

        $this->assertSame('', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('foo', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public function testSetAndAddCssClass()
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('')
            ->setCssClass('foo')->addCssClass('bar');

        $this->assertSame('foo', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('bar', $actionConfig->getAsDto()->getAddedCssClass());
    }

    public function testSetAndAddCssClassWithSpaces()
    {
        $actionConfig = Action::new(Action::DELETE)->linkToCrudAction('')
            ->setCssClass('      foo1   foo2  ')->addCssClass('     bar1    bar2   ');

        $this->assertSame('foo1   foo2', $actionConfig->getAsDto()->getCssClass());
        $this->assertSame('bar1    bar2', $actionConfig->getAsDto()->getAddedCssClass());
    }
}
