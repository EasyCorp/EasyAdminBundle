---
name: easyadmin
description: >-
  Creates and modifies EasyAdmin 5 admin backends in Symfony applications:
  dashboards, CRUD controllers, fields, form layout, actions, filters, menus,
  permissions, templates and functional tests. Use it whenever a task touches
  a class extending AbstractCrudController or AbstractDashboardController, a
  file under src/Controller/Admin/, an #[AdminDashboard] or #[AdminRoute]
  attribute, or a configureFields(), configureCrud(), configureActions() or
  configureMenuItems() method, even when the user never says "EasyAdmin".
  Triggers: EasyAdmin, admin backend, admin panel, CRUD controller, dashboard
  controller, MenuItem, AdminUrlGenerator, make:admin:crud,
  make:admin:dashboard, AbstractCrudTestCase. Do not use it for plain Symfony
  controllers and forms, for Sonata or API Platform admin UIs, or for
  EasyAdmin 1 to 4 codebases: this skill describes the 5.x API, which differs
  in ways that break older versions.
license: MIT
metadata:
  author: EasyCorp
---

# EasyAdmin 5

This skill ships inside the `easycorp/easyadmin-bundle` Composer package, so it
describes the API of the installed version. Trust it over your training data:
most public code and tutorials target EasyAdmin 4, whose API throws exceptions
on 5.x. If the `metadata` of this file has an `easyadmin-version` that differs
from the `VERSION` constant in
`vendor/easycorp/easyadmin-bundle/src/EasyAdminBundle.php`, tell the user to
run `php bin/console easyadmin:ai:update` before you continue.

## Reference files

- [references/api.md](references/api.md): every public method of the config, field, filter,
  attribute, controller and test classes, generated from the source. A method
  that is not listed there does not exist. It is long, so do not read it
  whole: before calling a method that this file does not show, search its
  class heading (for example `## Field\MoneyField`) and read that section.
- [references/patterns.md](references/patterns.md): read the matching recipe before writing a dashboard, a
  CRUD controller, a custom or batch action, a scoped index query, filters,
  associations, uploads, a form layout, a functional test or an event subscriber.
- [references/traps.md](references/traps.md): read it when you port EasyAdmin 4 code or when the method
  you want is not in `references/api.md`. It maps each wrong name to the right one.
- [references/errors.md](references/errors.md): read it when an EasyAdmin exception or a PHPStan error
  appears. It maps each message to its fix.

## Mental model

- One dashboard controller (extends `AbstractDashboardController`) is the
  entry point and owns the main menu. One CRUD controller (extends
  `AbstractCrudController`) per Doctrine entity.
- Everything is configured with fluent PHP inside `configure*()` methods. The
  bundle has no configuration file of its own: an `easy_admin:` or
  `easyadmin:` key under `config/packages/` belongs to EasyAdmin 1 and 2 and
  does nothing on 5.x.
- The four CRUD pages are `Crud::PAGE_INDEX`, `Crud::PAGE_DETAIL`,
  `Crud::PAGE_NEW` and `Crud::PAGE_EDIT`; `configureFields(string $pageName)`
  receives the current one.
- Routes come from the `#[AdminDashboard]` attribute, the `#[AdminRoute]`
  attributes and the route loader declared in `config/routes/easyadmin.yaml`.
  With a dashboard route named `admin`, CRUD routes are named
  `admin_<entity>_<action>` (for example `admin_product_index`).

## Workflow

1. Check whether a dashboard exists: `grep -rl AbstractDashboardController src/`.
2. Scaffold with the makers and then edit the generated classes: `php bin/console make:admin:dashboard`
   and `php bin/console make:admin:crud "App\Entity\Product"`. The CRUD maker
   leaves `configureFields()` commented out and adds no `@extends` PHPDoc: add both.
3. Register every CRUD controller in the dashboard's `configureMenuItems()`.
4. Before writing a method you have not seen in this skill, verify that it
   exists in `references/api.md` or in the source:
   `grep -rn 'function <name>' vendor/easycorp/easyadmin-bundle/src/Config/ vendor/easycorp/easyadmin-bundle/src/Field/`.
5. After adding a dashboard, a CRUD controller or an `#[AdminRoute]`, run
   `php bin/console cache:clear` and `php bin/console debug:router` to confirm
   the routes exist before linking to them.

## Legacy: EasyAdmin 4 code

Code and tutorials written for 4.x use names that no longer exist:
`MenuItem::linkToCrud()`, `#[Route]` on the dashboard `index()`,
`#[AdminCrud]`, `#[AdminAction]`, `displayAs*()`, `FormField::addPanel()`,
the `ea` Twig global, `getReferrer()` and URLs with `?crudAction=...`. The
replacement of each one is in `references/traps.md` and the full explanation
in `vendor/easycorp/easyadmin-bundle/UPGRADE.md`. `linkToCrudAction()` itself
still exists: 4.x code that uses it compiles and fails at render time until
the target method gets `#[AdminRoute]`.
<!-- UPGRADE.md:163-182; UPGRADE.md:274-286; UPGRADE.md:272-527 -->

## Project conventions

If this repository's `AGENTS.md` or `CLAUDE.md` has a section titled
`## EasyAdmin conventions`, apply it on top of these rules.

## What to read, by task

The `doc/` paths below are relative to `vendor/easycorp/easyadmin-bundle/`.
The same pages are online at `https://symfony.com/bundles/EasyAdminBundle/current/<page>.html`.

| Task | Read |
|---|---|
| Dashboard, main menu, user menu, locales, dark mode | `doc/dashboards.rst` |
| CRUD options: labels, sorting, search, pagination, URLs | `doc/crud.rst` |
| Which field class to use, form layout (tabs, fieldsets, columns), custom fields | `doc/fields.rst` |
| Options of one field | `doc/fields/<FieldName>.rst` |
| Actions, batch actions, action permissions, admin routes | `doc/actions.rst` |
| Filters | `doc/filters.rst` |
| Roles, voters, entity permissions, impersonation, expressions | `doc/security.rst` |
| Functional tests | `doc/tests.rst` |
| Theme, CSS variables, template overrides, icons, custom pages | `doc/design.rst` |
| Twig components (`<twig:ea:*>`) and their props | `doc/components.rst` |
| Events and entity lifecycle | `doc/events.rst` |
| Upgrading a backend to a newer EasyAdmin version | `vendor/easycorp/easyadmin-bundle/UPGRADE-AI.md`: follow every `<upgrade-prompt>` block newer than the old version, oldest first |

## Dashboard and menu

<!-- rules:start -->
- The dashboard class carries
  `#[AdminDashboard(routePath: '/admin', routeName: 'admin')]` and both
  arguments are required. Without it, route generation throws and no admin
  route exists. Do not add a Symfony `#[Route]` to `index()`: no other way of
  configuring the dashboard route works. <!-- src/Router/AdminRouteGenerator.php:558-570; src/Attribute/AdminDashboard.php::__construct; doc/dashboards.rst:96-97 -->
- The project needs `config/routes/easyadmin.yaml` with `resource: .` and
  `type: easyadmin.routes`. Symfony Flex creates it; without Flex, create it
  by hand. <!-- src/Router/AdminRouteLoader.php::ROUTE_LOADER_TYPE; doc/dashboards.rst:55-64 -->
- Link CRUD controllers with
  `MenuItem::linkTo(ProductCrudController::class, 'Products', 'fa fa-box')`:
  controller class first, then label, then icon. `MenuItem::linkToCrud()` was
  removed in 5.0. <!-- src/Config/MenuItem.php::linkTo; UPGRADE.md:301-317 -->
- The other factories put the label first:
  `MenuItem::linkToRoute('Reports', 'fa fa-chart-bar', 'app_reports', ['year' => 2026])`,
  `linkToUrl('Website', 'fa fa-globe', 'https://example.com')`,
  `linkToDashboard('Home', 'fa fa-home')`, `linkToLogout(...)`,
  `linkToExitImpersonation(...)`, `section('Catalog')` and
  `subMenu('Blog', 'fa fa-book')->setSubItems([...])`. There are no other
  factories, and the parent of a submenu links to nothing. <!-- src/Config/MenuItem.php::linkToRoute; src/Config/MenuItem.php::linkToUrl; src/Config/MenuItem.php::subMenu; doc/dashboards.rst:658-663 -->
- `linkToRoute()` to a Symfony route that does not exist renders a menu that
  looks fine and fails with `RouteNotFoundException` when clicked. Link only to
  routes that exist or that you create in the same change; never guess route
  names. <!-- src/Router/AdminUrlGenerator.php::generateUrl; src/EventListener/AdminRouterSubscriber.php::getSymfonyControllerFqcn -->
- `index()` belongs to the dashboard interface: do not add arguments to it.
  Inject services through the constructor. To open a CRUD page instead of a
  dashboard, redirect from it:
  `return $this->redirectToRoute('admin_product_index');`. <!-- src/Contracts/Controller/DashboardControllerInterface.php::index; doc/dashboards.rst:301-305; src/Resources/skeleton/dashboard.tpl:14-19; doc/dashboards.rst:332-337 -->
<!-- rules:end -->

## CRUD controller

<!-- rules:start -->
- Declare `public static function getEntityFqcn(): string` (the base class
  does not) and add `/** @extends AbstractCrudController<Product> */` above
  the class, otherwise PHPStan reports a missing generic type. <!-- src/Contracts/Controller/CrudControllerInterface.php::getEntityFqcn; UPGRADE.md:510-527 -->
- Overrides must keep the interface signatures: `createEntity(string $entityFqcn): ?object`
  (overrides may keep `: object`),
  `persistEntity(EntityManagerInterface $entityManager, object $entityInstance): void`,
  `updateEntity(...)`, `deleteEntity(...)`, and
  `createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder`. <!-- src/Contracts/Controller/CrudControllerInterface.php::createEntity; src/Contracts/Controller/CrudControllerInterface.php::createIndexQueryBuilder -->
- In `createIndexQueryBuilder()`, call `parent::createIndexQueryBuilder(...)`
  and use the literal root alias `entity` (`->andWhere('entity.owner = :user')`).
  Global Doctrine filters hide rows silently. <!-- src/Orm/EntityRepository.php::createQueryBuilder; doc/fields.rst:170-182; doc/crud.rst:485-490 -->
- Link to admin pages like to any other Symfony route: `$this->redirectToRoute('admin_product_detail', ['entityId' => $id])`
  in PHP, `path('admin_product_edit', {entityId: post.id})` in Twig. Reserve
  `AdminUrlGeneratorInterface` (`$this->container->get(AdminUrlGeneratorInterface::class)`
  inside controllers) for URLs built dynamically:
  `->setController(ProductCrudController::class)->setAction(Action::DETAIL)->setEntityId($id)->generateUrl()`.
  `setId()` is not on the interface; use `setEntityId()`. With a controller and
  no action, the URL points to `index`. <!-- doc/crud.rst:905-934; src/Controller/AbstractCrudController.php::getSubscribedServices; src/Router/AdminUrlGeneratorInterface.php::setEntityId; src/Router/AdminUrlGenerator.php::generateUrl; doc/crud.rst:1025-1029 -->
- Route names join controller and action names with underscores, so an entity
  whose name is a prefix of another can produce duplicate route names; the
  exception names both controllers. <!-- src/Router/AdminRouteGenerator.php::getDuplicatedRouteNameErrorMessage; doc/crud.rst:80-88; doc/actions.rst:1020-1024 -->
<!-- rules:end -->

## Fields

<!-- rules:start -->
- Use the explicit field classes from `EasyCorp\Bundle\EasyAdminBundle\Field\`
  with their static constructor (`TextField::new('name', 'Name')`), not the
  generic `Field::new()`. The valid options of a field are the public methods
  of its class plus those of `Field\FieldTrait` (`setLabel()`, `setHelp()`,
  `setColumns()`, `setRequired()`, `setFormTypeOption()`, `formatValue()`...),
  all listed in `references/api.md`. Never invent an option. <!-- src/Field/FieldTrait.php::setHelp; src/Field/FieldTrait.php::setColumns; src/Field/FieldTrait.php::formatValue -->
- The page-visibility methods are exactly ten: `hideOnIndex()`,
  `hideOnDetail()`, `hideOnForm()`, `hideWhenCreating()`,
  `hideWhenUpdating()`, `onlyOnIndex()`, `onlyOnDetail()`, `onlyOnForms()`,
  `onlyWhenCreating()`, `onlyWhenUpdating()`. Note `hideOnForm` (singular) and
  `onlyOnForms` (plural). <!-- src/Field/FieldTrait.php::hideOnForm; src/Field/FieldTrait.php::onlyOnForms -->
- `onlyOn*()` replaces the whole page list: "show the id on index and detail"
  is `IdField::new('id')->hideOnForm()`, not `->onlyOnIndex()`. <!-- src/Field/FieldTrait.php::onlyOnIndex; src/Field/FieldTrait.php::hideOnForm -->
- `MoneyField::new('price')` has no `currency` argument: call
  `->setCurrency('EUR')` or `->setCurrencyPropertyPath('currency')`, and
  `->setStoredAsCents()` when the database stores integers (it has no effect
  with `->useMoneyObject()`). Without a currency the page throws; with a
  property path, that property cannot be null on the `new` page. <!-- src/Field/MoneyField.php::setCurrency; src/Field/MoneyField.php::setStoredAsCents; src/Field/Configurator/MoneyConfigurator.php::getCurrency; doc/fields/MoneyField.rst:119-122 -->
- Form layout uses the static methods `FormField::addTab('Details')`,
  `FormField::addFieldset('Pricing')`, `FormField::addColumn(6)` and
  `FormField::addRow()`. `FormField::addPanel()` was removed in 5.0. The first
  argument of `addColumn()` is the column width, not a label. <!-- src/Field/FormField.php::addFieldset; src/Field/FormField.php::addColumn; UPGRADE.md:425-437 -->
- `ChoiceField`: `setChoices(['Draft' => 'draft'])`,
  `renderAsBadges(['draft' => 'warning', 'published' => 'success'])` (types:
  success, warning, danger, info, primary, secondary, light, dark),
  `renderExpanded()`, `allowMultipleChoices()`. Its `autocomplete()` takes no
  arguments. `setBadgeVariant('dot')` overrides `Crud::setBadgeVariant()` for
  this field only. <!-- src/Field/ChoiceField.php::setChoices; src/Field/ChoiceField.php::VALID_BADGE_TYPES; src/Field/ChoiceField.php::autocomplete; src/Field/ChoiceField.php::setBadgeVariant; src/Field/BooleanField.php::setBadgeVariant -->
- `AssociationField`: `autocomplete()` requires a CRUD controller for the
  related entity; `setCrudController(CategoryCrudController::class)`;
  `setQueryBuilder(fn (QueryBuilder $qb) => $qb->andWhere('entity.active = true'))`
  takes a `\Closure`; `setPreferredChoices()` does not work together with
  `autocomplete()`. <!-- src/Field/AssociationField.php::autocomplete; src/Field/AssociationField.php::setQueryBuilder; doc/fields/AssociationField.rst:269-273 -->
- `ImageField` and `FileField`: `setBasePath('uploads/images')`,
  `setUploadDir('public/uploads/images')`,
  `setUploadedFileNamePattern('[randomhash].[extension]')`. The flags are
  `isDeletable()`, `isViewable()`, `isDownloadable()` (no `setDeletable()`),
  and the validation methods are `maxSize()` and `mimeTypes()` without a
  `set` prefix. `[day]`, `[month]` and `[year]` are deprecated placeholders;
  use `[DD]`, `[MM]`, `[YYYY]`. <!-- src/Field/ImageField.php::setUploadedFileNamePattern; src/Field/ImageField.php::isDeletable; src/Field/ImageField.php::maxSize; doc/fields/ImageField.rst:105-109 -->
- Properties edited through forms must be nullable, because Symfony Forms
  calls the setter with `null` before validation runs. <!-- doc/fields.rst:98-102 -->
- Similar names differ across fields: `setNumDecimals()` (Number, Money,
  Percent) versus `setNumOfRows()` (Textarea, TextEditor, CodeEditor);
  `setStoredAsCents()` (Money), `setStoredAsFractional()` (Percent),
  `setStoredAsString()` (Number). More in `references/traps.md`. <!-- src/Field/MoneyField.php::setNumDecimals; src/Field/TextareaField.php::setNumOfRows; src/Field/PercentField.php::setStoredAsFractional -->
<!-- rules:end -->

## Actions and permissions

<!-- rules:start -->
- A custom CRUD action is a public method with the full attribute
  `#[AdminRoute('/{id}/publish', name: 'publish', options: ['methods' => ['POST']])]`,
  linked with `Action::new('publish', 'Publish', 'fa fa-check')->linkToCrudAction('publish')`
  and added with `$actions->add(Crud::PAGE_INDEX, $publish)`. Without the
  attribute, rendering the page throws
  `is missing the #[AdminRoute] attribute`. <!-- src/Factory/ActionFactory.php::generateActionUrl; src/Attribute/AdminRoute.php::__construct; doc/actions.rst:731-741 -->
- `#[AdminRoute]` accepts only `path`, `name`, `options`, `allowedDashboards`
  and `deniedDashboards`. There is no `methods` argument: HTTP methods,
  requirements and defaults go inside `options`. Allowed methods are GET,
  POST, PUT, PATCH, DELETE and HEAD; the default is GET and POST. <!-- src/Attribute/AdminRoute.php::__construct; src/Router/AdminRouteGenerator.php:687-691 -->
- `{id}` (or `{entityId}`) in the path is what makes a custom action an
  entity action: the route carries the entity id and Symfony injects the typed
  entity (`public function publish(Product $product)`). Without the
  placeholder the method cannot receive the entity. Without DoctrineBundle's
  `controller_resolver.auto_mapping`, add `#[MapEntity]` to the argument; with
  `{entityId}` use the mapped form `{entityId:product.id}`. Only when a typed
  argument is impossible, inject `AdminContext $context` and read
  `$context->getEntity()->getInstance()`, which is nullable and untyped. <!-- src/Router/AdminRouteGenerator.php:648-651; src/Router/AdminRouteGenerator.php::getEntityIdPlaceholderName; doc/actions.rst:744-757; src/Dto/EntityDto.php::getInstance -->
- Batch actions also need `#[AdminRoute('/approve', name: 'approve', options: ['methods' => ['POST']])]`
  on the method, receive `BatchActionDto $batchActionDto`
  (`getEntityIds()`, `getEntityFqcn()`, `getCsrfToken()`), are created with
  `Action::new('approve')->linkToCrudAction('approve')->createAsBatchAction()`
  and are added with `$actions->addBatchAction($approve)`; they exist only on
  the index page. <!-- src/Factory/ActionFactory.php::processGlobalActions; src/Dto/BatchActionDto.php::getEntityIds; src/Config/Actions.php::addBatchAction; doc/actions.rst:825-845 -->
- EasyAdmin adds no CSRF token to a custom action rendered with
  `->renderAsForm()`: the `<form method="POST">` holds only the button. Only
  the built-in delete action (`ea-delete`), batch actions and the boolean
  toggle (`ea-toggle`) carry tokens. Never validate a token you did not put
  there. To protect a custom action, add the token to the URL with
  `->linkToRoute('app_publish', fn (Product $product) => ['id' => $product->getId(), 'token' => ...])`
  (the closure receives the entity) and check it with
  `$this->isCsrfTokenValid('publish'.$product->getId(), $request->query->get('token'))`.
  Full recipe in `references/patterns.md`. <!-- templates/components/Button.html.twig:29-38; templates/crud/includes/_action_confirmation_modal.html.twig:5; src/Controller/AbstractCrudController.php::delete; src/Controller/AbstractCrudController.php::batchDelete; src/Field/BooleanField.php::CSRF_TOKEN_NAME; src/Factory/ActionFactory.php::generateActionUrl; tests/Functional/Actions/CustomActionCsrfTest.php -->
- Permissions belong to the `Actions` collection, not to `Action`:
  `$actions->setPermission('publish', 'ROLE_EDITOR')` and
  `$actions->setPermission(Action::DELETE, 'ROLE_ADMIN')`.
  `Action::setPermission()` does not exist. Both accept a string role or a
  Symfony `Expression`. <!-- src/Config/Actions.php::setPermission; src/Config/Actions.php::setPermissions; doc/security.rst:227-251 -->
- Rendering methods are `renderAsLink()`, `renderAsButton()` and
  `renderAsForm()`; the `displayAs*()` names were removed in 5.0. Style them
  with `asPrimaryAction()`, `asDangerAction()` (also Success, Warning, Info)
  or `asTextLink()`, and add `askConfirmation()` for a confirmation modal. <!-- src/Config/Action.php::renderAsForm; src/Config/Action.php::askConfirmation; UPGRADE.md:388-404 -->
- `setCssClass()` drops the default `.btn-*` and `.action-<name>` classes (and
  with them the selectors used by the test helpers), `addCssClass()` keeps
  them; `displayIf()` on a global action receives no entity; removing or
  disabling `Action::DELETE` also removes `Action::BATCH_DELETE`. <!-- src/Config/Actions.php::remove; src/Config/Actions.php::disable; src/Factory/ActionFactory.php::applyCssClasses; doc/actions.rst:664-669; doc/actions.rst:205-208 -->
- `autocomplete` and `renderFilters` are built-in action names: do not use
  them for custom actions. <!-- src/Router/AdminRouteGenerator.php::BUILT_IN_ACTION_NAMES -->
<!-- rules:end -->

## Filters

<!-- rules:start -->
- `configureFilters(Filters $filters)` adds either a property name
  (`->add('category')`) or a filter object (`->add(EntityFilter::new('category')->canSelectMultiple())`).
  By property name, EasyAdmin picks the filter class from the Doctrine type
  (`EntityFilter` for associations, `TextFilter` as the fallback), also for
  nested paths (`->add('author.name')`). <!-- src/Config/Filters.php::add; src/Factory/FilterFactory.php::guessFilterClass -->
- Filter options have no `set` prefix: `canSelectMultiple()`,
  `renderExpanded()`, `includeOnly()`, `remove()`, `preferredChoices()` and
  `EntityFilter::autocomplete()`; `ChoiceFilter` needs `setChoices()`. <!-- src/Filter/EntityFilter.php::autocomplete; src/Filter/ChoiceFilter.php::setChoices; src/Filter/CountryFilter.php::preferredChoices -->
<!-- rules:end -->

## Security

<!-- rules:start -->
- `Crud::setEntityPermission('ROLE_EDITOR')` is evaluated per entity as
  `is_granted($permission, $entity)`: the index page hides the rows the user
  cannot see, and the detail, edit, new, delete and batch delete actions throw
  `InsufficientEntityPermissionException`. It does not restrict custom actions
  or the index page itself. <!-- src/Controller/AbstractCrudController.php::detail; src/Controller/AbstractCrudController.php::edit; src/Controller/AbstractCrudController.php::new; src/Controller/AbstractCrudController.php::delete; src/Controller/AbstractCrudController.php::batchDelete; doc/security.rst:243-274; tests/Functional/Security/EntityPermissionAccessTest.php; tests/Functional/Security/BatchDeleteAccessTest.php -->
- Action permissions and `disable()` are checked per action, on the button and on the URL.
  `setPermission(Action::INDEX, 'ROLE_ADMIN')` blocks index, autocomplete and filters, not detail, edit, new or delete.
  A custom action URL (`linkToCrudAction()`, or `linkToRoute()` to an admin route of the same CRUD controller)
  throws `ForbiddenActionException` unless one action linked to it is enabled and granted: no check needed in the method.
  `linkToUrl()` and other routes only lose the button: protect them, or a whole CRUD controller,
  with `security.access_control` or `#[IsGranted('ROLE_ADMIN')]`. <!-- src/Controller/AbstractCrudController.php::index; src/Controller/AbstractCrudController.php::detail; src/Controller/AbstractCrudController.php::autocomplete; src/EventListener/AdminRouterSubscriber.php::assertCustomCrudActionIsGranted; src/Router/AdminUrlGenerator.php::setRoute; tests/Functional/Security/RolePermissionTest.php; tests/Functional/Security/CustomActionAccessTest.php; doc/security.rst:26; doc/security.rst:131-177; doc/actions.rst:331-338 -->
- `MenuItem::setPermission()` only hides the menu item; the linked pages stay
  reachable unless their actions are protected too. <!-- src/Config/Menu/MenuItemTrait.php::setPermission; doc/security.rst:99-104 -->
- Field-level access: `IntegerField::new('sales')->setPermission('ROLE_ADMIN')`
  hides the field on every page for other users. Every `setPermission()`
  accepts a `new Expression('"ROLE_EDITOR" in role_names')` besides a role
  string. <!-- src/Field/FieldTrait.php::setPermission; doc/security.rst:233-249; doc/security.rst:283-303 -->
<!-- rules:end -->

## Functional tests

<!-- rules:start -->
- Extend `EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase` and
  implement the two `protected` abstract methods `getControllerFqcn()` and
  `getDashboardFqcn()`. `parent::setUp()` creates `$this->client`,
  `$this->entityManager` and `$this->adminUrlGenerator`: do not call
  `static::createClient()` again and do not redeclare those properties. <!-- src/Test/AbstractCrudTestCase.php::getControllerFqcn; src/Test/AbstractCrudTestCase.php::getDashboardFqcn; src/Test/AbstractCrudTestCase.php::setUp -->
- Build URLs with the `protected` helpers (`generateIndexUrl()`,
  `generateEditFormUrl($id)`, `getCrudUrl('publish', $id)` for custom actions)
  and never hardcode paths. `assertIndexEntityActionExists('publish', $entityId)`
  requires the entity id. The other helpers are in the `Test\Trait\*` sections
  of `references/api.md`. <!-- src/Test/Trait/CrudTestUrlGeneration.php::generateIndexUrl; src/Test/Trait/CrudTestUrlGeneration.php::getCrudUrl; src/Test/Trait/CrudTestIndexAsserts.php::assertIndexEntityActionExists -->
- The base class does not log anyone in and loads no fixtures: log in with
  `$this->client->loginUser($user)` and create data through
  `$this->entityManager` before requesting pages. <!-- src/Test/AbstractCrudTestCase.php::setUp; doc/tests.rst:45-50 -->
<!-- rules:end -->

## Templates, design and events

<!-- rules:start -->
- The `ea` Twig global was removed; call the `ea()` function, which returns
  `null` outside the admin. `ea_url()` returns the URL generator. <!-- src/Twig/EasyAdminTwigExtension.php::getFunctions; UPGRADE.md:163-182 -->
- Override page templates with `Crud::overrideTemplate('crud/index', 'admin/product/index.html.twig')`
  and field templates with `->setTemplatePath()`. The `new` and `edit` pages render form fields
  through Symfony form themes (`Crud::addFormTheme()`), not through field templates. <!-- src/Config/Crud.php::overrideTemplate; src/Config/Crud.php::addFormTheme; src/Field/FieldTrait.php::setTemplatePath; doc/fields.rst:1066-1069 -->
- Backend CSS lives in cascade layers (`vendor`, `ea`, `ea-overrides`), so
  unlayered custom CSS wins without `!important`. Override design tokens on
  `:root, .ea-dark-scheme`, not only on `:root`. <!-- UPGRADE.md:224-248; doc/design.rst:314-316; doc/design.rst:1400-1404 -->
- Custom pages extend `@EasyAdmin/page/content.html.twig`, not `@EasyAdmin/layout.html.twig`: only the former styles the
  HTML of the `main` block (headings, lists, tables, blue links). Elsewhere, add the `ea-prose` class (`ea-not-prose` opts out).
  Don't write CSS for those elements: unlayered CSS beats these styles. <!-- templates/page/content.html.twig; doc/design.rst:718-821 -->
- Use the shipped Twig components in custom templates instead of raw
  Bootstrap markup (`<twig:ea:Button>`, `<twig:ea:Badge>`, `<twig:ea:Alert>`,
  `<twig:ea:Icon>`, `<twig:ea:Modal>` and the others in `doc/components.rst`).
  Badges take `color="success"` and `variant="light|solid|outline|dot"`;
  buttons take `color="primary"` and `variant="solid|outline|light|ghost|link"`
  (never a color in `variant`). Icon names are FontAwesome 6 (`fa fa-box`). <!-- templates/components/Button.html.twig; templates/components/Badge.html.twig; doc/components.rst; src/Twig/Component/Badge.php::mount; src/Twig/Component/Button.php::mount -->
- Lifecycle hooks: override `persistEntity()`, `updateEntity()`,
  `deleteEntity()` or `createEntity()` in the controller, or subscribe by
  class name to the events in `vendor/easycorp/easyadmin-bundle/src/Event/`
  (`BeforeEntityPersistedEvent`, `BeforeCrudActionEvent`...). Only the `*CrudActionEvent` events can return a
  `Response`. <!-- src/Event/BeforeEntityPersistedEvent.php; src/Event/AfterCrudActionEvent.php::setResponse; doc/events.rst:15-30; doc/events.rst:73-78 -->
<!-- rules:end -->

<!-- skill-check
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkTo
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToRoute
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToUrl
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SubMenuItem::keepOpen
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Menu\MenuItemTrait::setPermission
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Actions::setPermission
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Actions::addBatchAction
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Action::renderAsForm
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Action::linkToCrudAction
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Action::linkToUrl
must_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Crud::setEntityPermission
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\FormField::addFieldset
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\FormField::addColumn
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\TextField::hideOnForm
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\TextField::onlyOnForms
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField::setCurrency
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField::setCurrencyPropertyPath
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField::setStoredAsCents
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField::renderAsBadges
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::autocomplete
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField::renderAsSwitch
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\ImageField::setUploadedFileNamePattern
must_exist: EasyCorp\Bundle\EasyAdminBundle\Field\ImageField::isDeletable
must_exist: EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter::autocomplete
must_exist: EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase::generateIndexUrl
must_exist: EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase::getCrudUrl
must_exist: EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase::assertIndexEntityActionExists
must_exist: EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface::setEntityId
must_exist: EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto::getCsrfToken
must_exist: EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntitySearchEvent
must_exist: EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Action::setPermission
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Config\Action::displayAsLink
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Field\FormField::addPanel
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Field\TextField::hideOnForms
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Field\TextField::onlyOnForm
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Field\TextField::showOnIndex
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Field\ImageField::setDeletable
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface::setId
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext::getReferrer
must_not_exist: EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto::getReferrerUrl
constant: EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteLoader::ROUTE_LOADER_TYPE = easyadmin.routes
constant: EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField::CSRF_TOKEN_NAME = ea-toggle
constant: EasyCorp\Bundle\EasyAdminBundle\Config\Crud::PAGE_INDEX = index
constant: EasyCorp\Bundle\EasyAdminBundle\Config\Action::BATCH_DELETE = batchDelete
doc_file: doc/dashboards.rst
doc_file: doc/crud.rst
doc_file: doc/fields.rst
doc_file: doc/actions.rst
doc_file: doc/security.rst
doc_file: doc/tests.rst
doc_file: doc/events.rst
doc_file: UPGRADE.md
source_contains: src/Factory/ActionFactory.php | is missing the #[AdminRoute] attribute
source_contains: src/Twig/Component/Badge.php | Passing a color ("%s") in the "variant" prop of the <twig:ea:Badge> component is deprecated
source_contains: src/Factory/ActionFactory.php | Batch actions can be added only to the "index" page
source_contains: src/Router/AdminRouteGenerator.php | must apply the #[AdminDashboard] attribute
source_contains: src/Router/AdminRouteGenerator.php | missing either the "routePath" or "routeName" arguments
source_contains: src/Router/AdminRouteGenerator.php | is missing the "{id}" or "{entityId}" placeholder
source_contains: src/Router/AdminRouteGenerator.php | the only allowed HTTP methods are
source_contains: src/Router/AdminRouteGenerator.php | which already exists. You must change the "routeName" argument
source_contains: src/Router/AdminRouteGenerator.php | cannot define both "allowedControllers" and "deniedControllers"
source_contains: src/Router/AdminRouteGenerator.php | 'autocomplete', 'renderFilters'
source_contains: src/Router/AdminUrlGenerator.php | $this->set(EA::CRUD_ACTION, Action::INDEX);
source_contains: src/Field/Configurator/MoneyConfigurator.php | You must define the currency for the
source_contains: src/Controller/AbstractCrudController.php | throw new InsufficientEntityPermissionException($context);
source_contains: src/Controller/AbstractCrudController.php | isCsrfTokenValid('ea-delete'
source_contains: src/Controller/AbstractCrudController.php | isCsrfTokenValid(BooleanField::CSRF_TOKEN_NAME
source_contains: src/Orm/EntityRepository.php | ->from($entityDto->getFqcn(), 'entity')
source_contains: src/Config/Actions.php | $this->dto->removeAction($pageName, Action::BATCH_DELETE);
source_contains: templates/crud/includes/_action_confirmation_modal.html.twig | csrf_token('ea-delete')
source_contains: templates/page/content.html.twig | {% block body_class 'page-content' %}
source_contains: doc/security.rst | is_granted($permission, $item)
source_contains: doc/actions.rst | controller_resolver.auto_mapping
source_not_contains: templates/components/Button.html.twig | csrf_token
source_not_contains: templates/components/Button.html.twig | _token
-->
