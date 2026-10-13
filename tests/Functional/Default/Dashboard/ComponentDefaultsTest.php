<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Dashboard;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The default options of Twig components configured in the configureCrud() method of the
 * dashboard also apply to pages without a CRUD controller (e.g. the dashboard page).
 */
class ComponentDefaultsTest extends WebTestCase
{
    public function testDashboardBadgeVariantAppliesToPagesWithoutCrudController(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/component_defaults_admin');

        static::assertResponseIsSuccessful();
        $this->assertSame('badge badge-success badge-variant-dot', $crawler->filter('#badge-with-default-variant')->attr('class'));
        $this->assertCount(1, $crawler->filter('#badge-with-default-variant .badge-dot'));
        $this->assertSame('badge badge-success badge-variant-solid', $crawler->filter('#badge-with-explicit-variant')->attr('class'));
    }
}
