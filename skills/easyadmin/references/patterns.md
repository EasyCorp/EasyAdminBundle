# Patterns

Copy-ready recipes for EasyAdmin 5. Every class and method used here exists in
the installed version; adapt entity, property and role names. `App\Entity\Product`
is the running example, with `name`, `slug`, `description`, `priceInCents`,
`currency`, `status`, `isFeatured`, `imageFilename`, `category`, `tags`,
`shop` and `createdAt` properties.

## Table of contents

1. [Dashboard with a sectioned menu](#1-dashboard-with-a-sectioned-menu)
2. [Dashboard that opens a CRUD page](#2-dashboard-that-opens-a-crud-page)
3. [CRUD controller with fields per page](#3-crud-controller-with-fields-per-page)
4. [Custom POST action with permission and CSRF token](#4-custom-post-action-with-permission-and-csrf-token)
5. [Batch action](#5-batch-action)
6. [Index query scoped to the current user](#6-index-query-scoped-to-the-current-user)
7. [Filters](#7-filters)
8. [Associations](#8-associations)
9. [Image upload](#9-image-upload)
10. [Money and entity defaults](#10-money-and-entity-defaults)
11. [Form layout with tabs, fieldsets and columns](#11-form-layout-with-tabs-fieldsets-and-columns)
12. [Functional test](#12-functional-test)
13. [Event subscriber](#13-event-subscriber)

## 1. Dashboard with a sectioned menu

```php
// src/Controller/Admin/DashboardController.php
namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Acme Shop')
            ->setFaviconPath('favicon.svg')
            ->setLocales(['en', 'es']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Catalog');
        yield MenuItem::linkTo(ProductCrudController::class, 'Products', 'fa fa-box');
        yield MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fa fa-tags');

        yield MenuItem::section('Sales');
        yield MenuItem::linkToRoute('Orders', 'fa fa-cart-shopping', 'admin_orders');

        yield MenuItem::section();
        yield MenuItem::linkToUrl('Documentation', 'fa fa-book', 'https://example.com/help')
            ->setLinkTarget('_blank');
        yield MenuItem::linkToRoute('Settings', 'fa fa-gear', 'app_settings')
            ->setPermission('ROLE_ADMIN');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->displayUserAvatar()
            ->addMenuItems([
                MenuItem::linkToRoute('My profile', 'fa fa-user', 'app_profile'),
            ]);
    }
}
```

`admin_orders`, `app_settings` and `app_profile` must be existing Symfony
routes: a menu item pointing to an unknown route renders and then fails when
clicked.

## 2. Dashboard that opens a CRUD page

```php
public function index(): Response
{
    // the dashboard route name is 'admin' and the entity is Product; list the
    // generated names with "php bin/console debug:router"
    return $this->redirectToRoute('admin_product_index');
}
```

## 3. CRUD controller with fields per page

```php
// src/Controller/Admin/ProductCrudController.php
namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Product>
 */
class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Product')
            ->setEntityLabelInPlural('Products')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields(['name', 'slug'])
            ->setPaginatorPageSize(50);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name');
        yield SlugField::new('slug')->setTargetFieldName('name')->hideOnIndex();
        yield TextareaField::new('description')->hideOnIndex();
        yield AssociationField::new('category');
        yield ChoiceField::new('status')
            ->setChoices(['Draft' => 'draft', 'Published' => 'published', 'Archived' => 'archived'])
            ->renderAsBadges(['draft' => 'warning', 'published' => 'success', 'archived' => 'secondary']);
        yield BooleanField::new('isFeatured')->renderAsSwitch();
        yield DateTimeField::new('createdAt')->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->setPermission(Action::NEW, 'ROLE_EDITOR')
            ->setPermission(Action::EDIT, 'ROLE_EDITOR');
    }
}
```

`$pageName` lets you branch: `if (Crud::PAGE_INDEX === $pageName) { ... }`.
Field visibility methods are usually enough.

## 4. Custom POST action with permission and CSRF token

EasyAdmin renders a `renderAsForm()` action as a bare `<form method="post">`
without any CSRF token, so the token travels in the action URL. The
`linkToRoute()` closure receives the entity of each row, and the route name is
the one EasyAdmin generates for the `#[AdminRoute]` of the method
(`admin_product_publish` here).

```php
namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @extends AbstractCrudController<Product>
 */
class ProductCrudController extends AbstractCrudController
{
    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    // getEntityFqcn(), configureFields(), ...

    public function configureActions(Actions $actions): Actions
    {
        $publish = Action::new('publish', 'Publish', 'fa fa-check')
            ->linkToRoute('admin_product_publish', fn (Product $product): array => [
                'id' => $product->getId(),
                'token' => $this->csrfTokenManager->getToken('publish'.$product->getId())->getValue(),
            ])
            ->renderAsForm()
            ->displayIf(static fn (Product $product): bool => 'published' !== $product->getStatus());

        return $actions
            ->add(Crud::PAGE_INDEX, $publish)
            ->add(Crud::PAGE_DETAIL, $publish)
            ->setPermission('publish', 'ROLE_EDITOR');
    }

    #[AdminRoute('/{id}/publish', name: 'publish', options: ['methods' => ['POST']])]
    public function publish(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EDITOR');
        if (!$this->isCsrfTokenValid('publish'.$product->getId(), $request->query->get('token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $product->setStatus('published');
        $entityManager->flush();
        $this->addFlash('success', sprintf('"%s" is now published.', $product->getName()));

        return $this->redirectToRoute('admin_product_index');
    }
}
```

`setPermission('publish', ...)` hides the button from other users; the
`denyAccessUnlessGranted()` call protects the route itself. `{id}` in the path
lets Symfony inject the typed `Product` argument; without DoctrineBundle's
`controller_resolver.auto_mapping`, add `#[MapEntity]` to that argument.

## 5. Batch action

```php
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;

public function configureActions(Actions $actions): Actions
{
    $markAsDraft = Action::new('markAsDraft', 'Mark as draft', 'fa fa-file')
        ->linkToCrudAction('markAsDraft');

    return $actions
        ->addBatchAction($markAsDraft)
        ->setPermission('markAsDraft', 'ROLE_EDITOR');
}

#[AdminRoute('/mark-as-draft', name: 'mark_as_draft', options: ['methods' => ['POST']])]
public function markAsDraft(BatchActionDto $batchActionDto, EntityManagerInterface $entityManager): Response
{
    $this->denyAccessUnlessGranted('ROLE_EDITOR');
    if (!$this->isCsrfTokenValid('ea-batch-action-markAsDraft-'.Product::class, $batchActionDto->getCsrfToken())) {
        throw $this->createAccessDeniedException('Invalid CSRF token.');
    }

    // the submitted IDs are user input: load the entities yourself and check every one of them
    $products = $entityManager->getRepository(Product::class)->findBy(['id' => $batchActionDto->getEntityIds()]);
    foreach ($products as $product) {
        if (!$this->isGranted('ROLE_ADMIN') && $product->getShop()->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $product->setStatus('draft');
    }
    $entityManager->flush();

    return $this->redirectToRoute('admin_product_index');
}
```

Batch actions submit a form that carries the token EasyAdmin generated with
the id `ea-batch-action-<actionName>-<entityFqcn>`; the DTO exposes it through
`getCsrfToken()`. Their route path has no `{id}` placeholder. Everything else
in the DTO (`getEntityIds()`, `getEntityFqcn()`) comes from the submitted form:
use your own entity class in the token id and in the query, and apply to every
entity the same access rules as the index query (recipe 6), because a user can
submit IDs that the index page never showed. The built-in `batchDelete()` does
the same with `setEntityPermission()`.

## 6. Index query scoped to the current user

```php
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
{
    $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

    if (!$this->isGranted('ROLE_ADMIN')) {
        $queryBuilder
            ->join('entity.shop', 'shop')
            ->andWhere('shop.owner = :owner')
            ->setParameter('owner', $this->getUser());
    }

    return $queryBuilder;
}
```

The root alias of the query is always `entity`. This scopes the index page
only; combine it with `Crud::setEntityPermission()` and a voter to also
protect the detail, edit and delete pages of entities the user does not own.

## 7. Filters

```php
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

public function configureFilters(Filters $filters): Filters
{
    return $filters
        ->add(EntityFilter::new('category')->canSelectMultiple())
        ->add(BooleanFilter::new('isFeatured'))
        ->add(DateTimeFilter::new('createdAt'))
        ->add(ChoiceFilter::new('status')->setChoices(['Draft' => 'draft', 'Published' => 'published']));
}
```

`->add('category')` (a property name) works too and picks the filter class
from the Doctrine type.

## 8. Associations

```php
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

// ManyToOne with a remote autocomplete: CategoryCrudController must exist
yield AssociationField::new('category')->autocomplete();

// ManyToMany: 'by_reference' => false makes Symfony call addTag()/removeTag()
yield AssociationField::new('tags')
    ->setFormTypeOption('by_reference', false)
    ->hideOnIndex();

// Restrict the selectable items
yield AssociationField::new('shop')->setQueryBuilder(
    fn (QueryBuilder $queryBuilder): QueryBuilder => $queryBuilder->andWhere('entity.active = true')
);
```

## 9. Image upload

```php
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

yield ImageField::new('imageFilename', 'Image')
    ->setBasePath('uploads/products')
    ->setUploadDir('public/uploads/products')
    ->setUploadedFileNamePattern('[randomhash].[extension]')
    ->setRequired(false);
```

The entity property stores the file name as a string. `setUploadDir()` is
relative to the project directory and `setBasePath()` to the public directory.

## 10. Money and entity defaults

```php
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

// in configureFields()
yield MoneyField::new('priceInCents', 'Price')
    ->setCurrencyPropertyPath('currency')
    ->setStoredAsCents();

// the currency property must not be null when the 'new' form renders
public function createEntity(string $entityFqcn): object
{
    $product = new Product();
    $product->setCurrency('EUR');

    return $product;
}
```

For a single currency, `->setCurrency('EUR')` replaces the property path and
the `createEntity()` override.

## 11. Form layout with tabs, fieldsets and columns

```php
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

yield FormField::addTab('Basic information', 'fa fa-circle-info');
yield FormField::addFieldset('Identity');
yield TextField::new('name');
yield SlugField::new('slug')->setTargetFieldName('name');
yield FormField::addFieldset('Content')->collapsible();
yield TextEditorField::new('description');

yield FormField::addTab('Pricing', 'fa fa-euro-sign');
yield FormField::addColumn(6);
yield MoneyField::new('priceInCents', 'Price')->setCurrency('EUR')->setStoredAsCents();
yield FormField::addColumn(6);
yield BooleanField::new('isFeatured');
```

Layout fields only affect the `new` and `edit` pages. `addColumn()` takes the
width (1 to 12) first and an optional label second.

## 12. Functional test

```php
// tests/Controller/Admin/ProductCrudControllerTest.php
namespace App\Tests\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Controller\Admin\ProductCrudController;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;

class ProductCrudControllerTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return ProductCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    public function testIndexListsProducts(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'editor@example.com']);
        $this->client->loginUser($user);

        $this->client->request('GET', $this->generateIndexUrl());

        static::assertResponseIsSuccessful();
        $this->assertIndexColumnExists('name');
        $this->assertIndexPageEntityCount(1);
    }

    public function testPublishActionIsListedForEditors(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'editor@example.com']);
        $this->client->loginUser($user);

        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertIndexEntityActionExists('publish', 1);
    }
}
```

`parent::setUp()` creates `$this->client` and `$this->entityManager`; load
fixtures yourself before the request. `getCrudUrl('publish', $id)` builds the
URL of a custom action.

## 13. Event subscriber

```php
// src/EventSubscriber/ProductSlugSubscriber.php
namespace App\EventSubscriber;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProductSlugSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [BeforeEntityPersistedEvent::class => 'setSlug'];
    }

    public function setSlug(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if (!$entity instanceof Product) {
            return;
        }

        $entity->setSlug($this->slugger->slug($entity->getName())->lower()->toString());
    }
}
```

Overriding `persistEntity()` in the controller is the alternative when the
logic belongs to a single CRUD.
