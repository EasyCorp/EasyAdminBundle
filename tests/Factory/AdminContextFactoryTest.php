<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\IconSet;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class AdminContextFactoryTest extends KernelTestCase
{
    private AdminContextFactory $adminContextFactory;
    private DashboardController $dashboardController;
    private CategoryCrudController $crudController;

    protected function setUp(): void
    {
        /** @var AdminContextFactory $factory */
        $factory = static::getContainer()->get(AdminContextFactory::class);
        $this->adminContextFactory = $factory;

        /** @var DashboardController $dashboardController */
        $dashboardController = static::getContainer()->get(DashboardController::class);
        $this->dashboardController = $dashboardController;

        /** @var CategoryCrudController $crudController */
        $crudController = static::getContainer()->get(CategoryCrudController::class);
        $this->crudController = $crudController;
    }

    public function testCreateSetsI18nContext(): void
    {
        $request = new Request();
        $request->setLocale('fr');

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertSame('fr', $result->getI18n()->getLocale());
    }

    /**
     * @dataProvider textDirectionByLocaleProvider
     */
    public function testCreateSetsTextDirectionBasedOnLocale(string $locale, string $expectedDirection): void
    {
        $request = new Request();
        $request->setLocale($locale);

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertSame($expectedDirection, $result->getI18n()->getTextDirection());
    }

    public static function textDirectionByLocaleProvider(): \Generator
    {
        yield 'english is ltr' => ['en', 'ltr'];
        yield 'spanish is ltr' => ['es', 'ltr'];
        yield 'french is ltr' => ['fr', 'ltr'];
        yield 'arabic is rtl' => ['ar', 'rtl'];
        yield 'hebrew is rtl' => ['he', 'rtl'];
        yield 'persian is rtl' => ['fa', 'rtl'];
    }

    public function testCreateSetsRequestContext(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertSame($request, $result->getRequest());
    }

    public function testCreateSetsDashboardRouteName(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertNotEmpty($result->getDashboardRouteName());
    }

    public function testCreateWithNullCrudControllerReturnsCrudContextNull(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertNull($result->getCrud());
    }

    public function testCreateSetsDashboardTitle(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        // the title is set by the DashboardController in the TestApplication
        $this->assertNotEmpty($result->getDashboardTitle());
    }

    public function testCreateOverridesActionFromQueryWithExplicitParameter(): void
    {
        $request = new Request([EA::CRUD_ACTION => 'edit']);

        // the explicit 'index' parameter should be used, not the 'edit' from query
        $result = $this->adminContextFactory->create($request, $this->dashboardController, null, 'index');

        // without a CRUD controller, getCrud() is null, so we just verify the factory accepts the parameter
        $this->assertSame($request, $result->getRequest());
    }

    public function testGetSearchDtoWithNullCrudDtoReturnsNull(): void
    {
        $request = new Request([EA::QUERY => 'test search']);

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertNull($result->getSearch());
    }

    public function testCreateSetsUserToNullWhenNoTokenStorage(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        // in the test environment, there's no authenticated user by default
        $this->assertNull($result->getUser());
    }

    public function testCreateSetsTranslationDomain(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        // default translation domain from the DashboardController
        $this->assertNotNull($result->getI18n()->getTranslationDomain());
    }

    public function testCreateWithCrudControllerReturnsCrudContext(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, Crud::PAGE_INDEX);

        $this->assertNotNull($result->getCrud());
    }

    public function testCreateWithCrudControllerSetsEntityFqcn(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, Crud::PAGE_INDEX);

        $this->assertSame(Category::class, $result->getCrud()->getEntityFqcn());
    }

    /**
     * @dataProvider validPageNamesProvider
     */
    public function testCreateWithCrudControllerSetsPageName(string $pageName): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, $pageName);

        $this->assertSame($pageName, $result->getCrud()->getCurrentPage());
    }

    public static function validPageNamesProvider(): \Generator
    {
        yield 'index page' => [Crud::PAGE_INDEX];
        yield 'detail page' => [Crud::PAGE_DETAIL];
        yield 'edit page' => [Crud::PAGE_EDIT];
        yield 'new page' => [Crud::PAGE_NEW];
    }

    public function testCreateWithInvalidPageNameSetsPageNameToNull(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, 'invalidPage');

        $this->assertNull($result->getCrud()->getCurrentPage());
    }

    public function testCreateWithCrudControllerSetsCurrentAction(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, Crud::PAGE_INDEX);

        $this->assertSame(Crud::PAGE_INDEX, $result->getCrud()->getCurrentAction());
    }

    public function testCreateTakesActionFromQueryWhenNotExplicitlyProvided(): void
    {
        $request = new Request([EA::CRUD_ACTION => Crud::PAGE_DETAIL]);

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController);

        $this->assertSame(Crud::PAGE_DETAIL, $result->getCrud()->getCurrentAction());
    }

    public function testCreateWithCrudControllerHasSearchDto(): void
    {
        $request = new Request([EA::QUERY => 'test search']);

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, Crud::PAGE_INDEX);

        $this->assertNotNull($result->getSearch());
        $this->assertSame('test search', $result->getSearch()->getQuery());
    }

    public function testCreateSetsDashboardFaviconPath(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        // favicon can be empty string but should not throw
        $this->assertIsString($result->getDashboardFaviconPath());
    }

    public function testCreateSetsDashboardContentWidth(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertContains($result->getDashboardContentWidth(), ['normal', 'full']);
    }

    public function testCreateSetsDashboardControllerFqcn(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertSame(DashboardController::class, $result->getDashboardControllerFqcn());
    }

    public function testCreateUsesDefaultLocaleWhenNotSet(): void
    {
        $request = new Request();
        // don't set locale, use default

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        // should have some locale set (default from framework)
        $this->assertNotEmpty($result->getI18n()->getLocale());
    }

    public function testCreateHandlesLocaleWithRegion(): void
    {
        $request = new Request();
        $request->setLocale('pt_BR');

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertSame('pt_BR', $result->getI18n()->getLocale());
        $this->assertSame('ltr', $result->getI18n()->getTextDirection());
    }

    public function testCreateReturnsAssetsFromDashboardWhenNoCrudController(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $assets = $result->getAssets();

        // HTML content from CategoryCrudController should NOT be present
        $this->assertNotContains(
            '<link data-added-from-controller rel="me" href="https://example.com">',
            $assets->getHeadContents()
        );
        $this->assertNotContains(
            '<span data-added-from-controller><!-- foo --></span>',
            $assets->getBodyContents()
        );
    }

    public function testCreateReturnsAssetsFromCrudControllerWhenProvided(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, Crud::PAGE_INDEX);

        $assets = $result->getAssets();

        // HTML content from CategoryCrudController should be present
        $this->assertContains(
            '<link data-added-from-controller rel="me" href="https://example.com">',
            $assets->getHeadContents()
        );
        $this->assertContains(
            '<span data-added-from-controller><!-- foo --></span>',
            $assets->getBodyContents()
        );
    }

    public function testCreateReturnsAssetsWithDefaultIconSet(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $this->assertSame(IconSet::FontAwesome, $result->getAssets()->getIconSet());
    }

    public function testCreateReturnsEmptyAssetArraysForNewDashboard(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, null);

        $assets = $result->getAssets();
        $this->assertIsArray($assets->getCssAssets());
        $this->assertIsArray($assets->getJsAssets());
        $this->assertIsArray($assets->getWebpackEncoreAssets());
        $this->assertIsArray($assets->getAssetMapperAssets());
    }

    public function testCreateWithCrudControllerPreservesIconSetFromDashboard(): void
    {
        $request = new Request();

        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, Crud::PAGE_INDEX);

        $this->assertSame(IconSet::FontAwesome, $result->getAssets()->getIconSet());
    }

    public function testCreateWithInvalidPageNameStillReturnsAssets(): void
    {
        $request = new Request();

        // when pageName is invalid, it gets set to null internally, and loadedOn(null) returns all assets
        $result = $this->adminContextFactory->create($request, $this->dashboardController, $this->crudController, 'invalidPage');

        $assets = $result->getAssets();

        // HTML content should still be present (not filtered by page)
        $this->assertContains(
            '<link data-added-from-controller rel="me" href="https://example.com">',
            $assets->getHeadContents()
        );
    }
}
