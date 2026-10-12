<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\DataCollector;

use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\IconSet;
use EasyCorp\Bundle\EasyAdminBundle\DataCollector\EasyAdminDataCollector;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\UiKit\UiKitController;

class EasyAdminDataCollectorTest extends AbstractCrudTestCase
{
    protected EntityRepository $categories;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categories = $this->entityManager->getRepository(Category::class);

        $this->client->enableProfiler();
    }

    protected function getControllerFqcn(): string
    {
        return CategoryCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testIndex(): void
    {
        $sort = ['name' => 'DESC', 'slug' => 'ASC'];
        $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query([EA::SORT => $sort, EA::QUERY => 'Category 1', EA::PAGE => 2]));

        $data = $this->getCollector()->getData();

        $this->assertSame([
            'title' => 'EasyAdmin Tests',
            'controller_fqcn' => DashboardController::class,
            'route_name' => 'admin',
            'locale' => 'en',
            'text_direction' => 'ltr',
            'translation_domain' => 'messages',
            'icon_set' => IconSet::FontAwesome,
            'theme' => null,
        ], $data['dashboard']);
        $this->assertSame('admin_category_index', $data['route']['name']);
        $this->assertSame('/admin/category', $data['route']['path']);
        $this->assertSame(CategoryCrudController::class.'::index', $data['route']['controller']->getValue());
        $this->assertSame([
            'controller_fqcn' => CategoryCrudController::class,
            'entity_fqcn' => Category::class,
            'action' => Action::INDEX,
            'page' => Crud::PAGE_INDEX,
            'entity_id' => null,
        ], $data['crud']);
        $this->assertNull($data['admin_route']);
        $this->assertSame($sort, $data['list_state']['sort']->getValue(true));
        $this->assertNull($data['list_state']['filters']->getValue(true));
        $this->assertSame('Category 1', $data['list_state']['query']->getValue());
        $this->assertSame('2', $data['list_state']['page']->getValue());
        $this->assertSame('/admin/_ea_ui_kit', $data['ui_kit_url']);
    }

    public function testIndexWithNonScalarPageNumber(): void
    {
        $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query([EA::PAGE => [2]]));

        $data = $this->getCollector()->getData();

        $this->assertSame(['2'], $data['list_state']['page']->getValue(true));
    }

    public function testEdit(): void
    {
        /** @var Category $category */
        $category = $this->categories->findOneBy([]);

        $this->client->request('GET', $this->generateEditFormUrl($category->getId()));

        $data = $this->getCollector()->getData();

        $this->assertSame('admin_category_edit', $data['route']['name']);
        $this->assertSame([
            'controller_fqcn' => CategoryCrudController::class,
            'entity_fqcn' => Category::class,
            'action' => Action::EDIT,
            'page' => Crud::PAGE_EDIT,
            'entity_id' => (string) $category->getId(),
        ], $data['crud']);
        $this->assertNull($data['admin_route']);
        $this->assertNull($data['list_state']);
    }

    public function testCustomAdminRoute(): void
    {
        $this->client->request('GET', '/admin/_ea_ui_kit');

        $data = $this->getCollector()->getData();

        $this->assertSame('admin_ea_ui_kit_index', $data['route']['name']);
        $this->assertNull($data['crud']);
        $this->assertSame([
            'controller_fqcn' => UiKitController::class,
            'method' => 'index',
        ], $data['admin_route']);
        $this->assertNull($data['list_state']);
    }

    public function testReset(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $collector = $this->getCollector();

        $this->assertTrue($collector->isEasyAdminRequest());

        $collector->reset();

        $this->assertFalse($collector->isEasyAdminRequest());
        $this->assertSame([], $collector->getData());
    }

    private function getCollector(): EasyAdminDataCollector
    {
        /** @var EasyAdminDataCollector $collector */
        $collector = $this->client->getProfile()->getCollector('easyadmin');

        $this->assertTrue($collector->isEasyAdminRequest());

        return $collector;
    }
}
