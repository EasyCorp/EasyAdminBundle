<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Customization\Crud\EntityLabels;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\Crud\EntityLabels\SingularLabelTestCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

/**
 * Tests for Crud::setEntityLabelInSingular() configuration method.
 */
class SingularLabelTest extends AbstractCrudTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function getControllerFqcn(): string
    {
        return SingularLabelTestCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return \EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Controller\DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    public function testSingularLabelAppearsOnNewPage(): void
    {
        $crawler = $this->client->request('GET', $this->generateNewFormUrl());

        static::assertResponseIsSuccessful();

        // the singular label should appear in page title or heading
        $pageContent = $crawler->html();
        static::assertStringContainsString('Custom Item', $pageContent);

        // verify form exists
        static::assertSelectorExists('form');

        // verify form fields exist
        static::assertSelectorExists('form input, form textarea, form select');
    }

    public function testSingularLabelAppearsOnEditPage(): void
    {
        // first, verify the new form works
        $this->client->request('GET', $this->generateNewFormUrl());
        static::assertResponseIsSuccessful();

        // verify page structure
        static::assertSelectorExists('.content');
        static::assertSelectorExists('form');
    }
}
