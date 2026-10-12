<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Component;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AbstractFieldFunctionalTest;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Rendering tests for the <twig:ea:Sidebar:Item> component.
 */
class SidebarItemTest extends AbstractFieldFunctionalTest
{
    private function renderItem(string $template): Crawler
    {
        return new Crawler(static::getContainer()->get('twig')->createTemplate($template)->render());
    }

    public function testItemWithoutCustomCssClass(): void
    {
        $link = $this->renderItem('<twig:ea:Sidebar:Item label="Orders" href="/admin/orders" />')->filter('a');

        $this->assertSame('ea-sidebar-item-link', $link->attr('class'));
        $this->assertSame('/admin/orders', $link->attr('href'));
    }

    public function testItemWithCustomCssClass(): void
    {
        $link = $this->renderItem('<twig:ea:Sidebar:Item label="Orders" href="/admin/orders" cssClass="text-danger" />')->filter('a');

        $this->assertSame('ea-sidebar-item-link text-danger', $link->attr('class'));
    }
}
