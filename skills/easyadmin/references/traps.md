# Naming traps

Method names that look right and are wrong on EasyAdmin 5. Every "Use" entry
exists in the source; every "Wrong" entry does not, or does something else.
When in doubt, search `references/api.md`.

## Table of contents

- [Menu](#menu)
- [Fields](#fields)
- [Actions and routes](#actions-and-routes)
- [Filters](#filters)
- [URL generation](#url-generation)
- [Twig](#twig)
- [Tests](#tests)
- [Assets](#assets)

## Menu

| Wrong | Use |
|---|---|
| `MenuItem::linkToCrud('Products', 'fa fa-box', Product::class)` | `MenuItem::linkTo(ProductCrudController::class, 'Products', 'fa fa-box')` |
| `MenuItem::linkTo('Products', 'fa fa-box', ProductCrudController::class)` (label first) | Controller class first, then label, then icon |
| `MenuItem::linkToRoute('app_reports')` (route first) | `MenuItem::linkToRoute('Reports', 'fa fa-chart-bar', 'app_reports')`: label, icon, route, parameters |
| `->setIcon('fa fa-box')` on a menu item | Pass the icon as the constructor argument; there is no `setIcon()` on menu items |
| `->setSubItems()` on `linkTo()`, `section()` or `linkToRoute()` | Only `MenuItem::subMenu('Blog', 'fa fa-book')->setSubItems([...])` |
| `->setRole('ROLE_ADMIN')` | `->setPermission('ROLE_ADMIN')` |
| `->setTarget('_blank')` | `->setLinkTarget('_blank')` |

## Fields

| Wrong | Use |
|---|---|
| `->hideOnForms()`, `->onlyOnForm()` | `->hideOnForm()` (singular), `->onlyOnForms()` (plural) |
| `->hideOnAll()`, `->showOnIndex()`, `->showOnForm()` | Ten methods only: `hideOnIndex`, `hideOnDetail`, `hideOnForm`, `hideWhenCreating`, `hideWhenUpdating`, `onlyOnIndex`, `onlyOnDetail`, `onlyOnForms`, `onlyWhenCreating`, `onlyWhenUpdating` |
| `->onlyOnIndex()` to also show a field on detail | `->hideOnForm()` (`onlyOn*` replaces the whole page list) |
| `->setReadOnly()` | `->setDisabled()` or `->setFormTypeOption('disabled', true)` |
| `->setPlaceholder('...')` | `->setFormTypeOption('attr', ['placeholder' => '...'])` |
| `->setChoices()` on `AssociationField` | `->setQueryBuilder(\Closure)` or `->setFormTypeOption('choices', ...)`; `setChoices()` belongs to `ChoiceField` |
| `MoneyField::new('price', 'Price', 'EUR')` or `currency: 'EUR'` | `MoneyField::new('price')->setCurrency('EUR')` or `->setCurrencyPropertyPath('currency')` |
| `MoneyField::setDecimals()` | `->setNumDecimals(2)` (also on `NumberField`, `PercentField`) |
| `TextareaField::setNumDecimals()` / `TextEditorField::setRows()` | `->setNumOfRows(10)` (`TextareaField`, `TextEditorField`, `CodeEditorField`) |
| `PercentField::setStoredAsCents()` | `->setStoredAsFractional()` (`MoneyField` has `setStoredAsCents()`, `NumberField` has `setStoredAsString()`) |
| `DateTimeField::setDateFormat('dd/MM/yyyy')` | `->setFormat('dd/MM/yyyy')` on the field; `Crud::setDateFormat()` sets the global default |
| `ImageField::setDeletable()`, `setViewable()`, `setDownloadable()` | `->isDeletable()`, `->isViewable()`, `->isDownloadable()` |
| `ImageField::setMaxSize()`, `setMimeTypes()` | `->maxSize('2M')`, `->mimeTypes('image/png')` |
| `setUploadedFileNamePattern('[year]/[month]/[day]/[slug]')` | `[YYYY]/[MM]/[DD]` (`[day]`, `[month]`, `[year]` are deprecated) |
| `ChoiceField::autocomplete(true)` | `ChoiceField::autocomplete()` takes no arguments; `AssociationField::autocomplete()` does |
| `AssociationField::setQueryBuilder([$this, 'method'])` | Pass a `\Closure`: `->setQueryBuilder(fn (QueryBuilder $qb) => $qb->andWhere('entity.active = true'))` |
| `AssociationField::setPreferredChoices()` together with `autocomplete()` | One or the other; preferred choices work with the native and local widgets only |
| `TextField::includeOnly()`, `ChoiceField::remove()` | `includeOnly()` and `remove()` exist only on `CountryField`, `LanguageField` and `LocaleField` |
| `BooleanField::renderAsToggle()` | `->renderAsSwitch()` |
| `FormField::addPanel('Details')` | `FormField::addFieldset('Details')` |
| `FormField::addColumn('Details')` | `FormField::addColumn(6, 'Details')`: width first, label second |
| `CollectionField::setEntryFormType()` | `->setEntryType(AddressType::class)` or `->useEntryCrudForm(AddressCrudController::class)` |
| `Field::new('name')->setType(TextType::class)` | Use the field class (`TextField::new('name')`) or `->setFormType(TextType::class)` |

## Actions and routes

| Wrong | Use |
|---|---|
| `Action::new('publish')->setPermission('ROLE_EDITOR')` | `$actions->setPermission('publish', 'ROLE_EDITOR')` |
| `->displayAsLink()`, `->displayAsButton()`, `->displayAsForm()` | `->renderAsLink()`, `->renderAsButton()`, `->renderAsForm()` |
| `->setCssClass('btn btn-primary')` to color a button | `->asPrimaryAction()`, `->asSuccessAction()`, `->asDangerAction()`, ...; `setCssClass()` removes the default classes |
| `#[AdminRoute('/{id}/publish', methods: ['POST'])]` | `#[AdminRoute('/{id}/publish', name: 'publish', options: ['methods' => ['POST']])]` |
| `#[AdminRoute('/publish', name: 'publish')]` on an entity action | Add `{id}`: without a placeholder the route carries no entity id and the method cannot receive the entity |
| `#[Route('/admin/product/{id}/publish')]` on a CRUD controller method | `#[AdminRoute]`; Symfony routes on CRUD methods are not admin routes |
| `#[AdminCrud]`, `#[AdminAction]` | `#[AdminRoute]` (class level for prefixes, method level for actions) |
| `->linkToCrudAction('publish')` without `#[AdminRoute]` on `publish()` | Add the attribute; the page throws otherwise |
| `$actions->add(Crud::PAGE_INDEX, 'publish')` for a custom action | Pass an `Action` object; a string only names a built-in action |
| `$actions->addBatchAction(Crud::PAGE_INDEX, $approve)` | `$actions->addBatchAction($approve)`; batch actions live on the index page only |
| `$actions->remove(Action::DELETE)` | `$actions->remove(Crud::PAGE_INDEX, Action::DELETE)` or `$actions->disable(Action::DELETE)` |
| `->setConfirmation('Are you sure?')` | `->askConfirmation()` |
| Custom action named `autocomplete` or `renderFilters` | Reserved names; pick another |

## Filters

| Wrong | Use |
|---|---|
| `$filters->add(TextFilter::new('name')->setCanSelectMultiple())` | `->canSelectMultiple()` (no `set` prefix; not on `TextFilter`) |
| `ChoiceFilter::new('status', choices: [...])` | `ChoiceFilter::new('status')->setChoices(['Draft' => 'draft'])` |
| `EntityFilter::new('category')->setAutocomplete()` | `->autocomplete()` |
| `BooleanFilter::new('active')->setChoices()` | Boolean filters have no choices |
| `Filters::new()->add(...)` returned from `configureFilters()` | Use the `$filters` argument: `return $filters->add('category');` |

## URL generation

| Wrong | Use |
|---|---|
| `$adminUrlGenerator->setId($id)` with the interface type | `->setEntityId($id)` (`setId()` is not on `AdminUrlGeneratorInterface`) |
| `->setController(ProductCrudController::class)->generateUrl()` expecting the current action | With a controller and no action the URL points to `index` |
| `->setReferrer(...)`, `$context->getReferrer()` | Removed; redirect to a generated admin URL or read the `Referer` header |
| `$this->get(AdminUrlGenerator::class)` | `$this->container->get(AdminUrlGeneratorInterface::class)` |
| `AdminUrlGenerator` to link to a page whose route name you know | `$this->redirectToRoute('admin_product_index')` in PHP, `path('admin_product_index')` in Twig; the generator is for URLs built dynamically |

## Twig

| Wrong | Use |
|---|---|
| `{{ ea.dashboardTitle }}`, `{% if ea.crud %}` | `{{ ea().dashboardTitle }}`, `{% if ea() and ea().crud %}` |
| `<button class="btn btn-primary">` inside admin templates | `<twig:ea:Button>` and the other `<twig:ea:*>` components (props in `doc/components.rst`) |

## Tests

| Wrong | Use |
|---|---|
| `public function getControllerFqcn()` | `protected function getControllerFqcn(): string` (also `getDashboardFqcn()`) |
| `self::createClient()` in `setUp()` | `parent::setUp()` already created `$this->client` |
| `$this->assertIndexEntityActionExists('publish')` | `$this->assertIndexEntityActionExists('publish', $entityId)` |
| `$this->generateCrudUrl('publish', $id)` | `$this->getCrudUrl('publish', $id)` |
| `$this->client->request('GET', '/admin/product')` | `$this->client->request('GET', $this->generateIndexUrl())` |

## Assets

| Wrong | Use |
|---|---|
| `Asset::new('app.js')->hideOnIndex()` | `->ignoreOnIndex()`, `->ignoreOnDetail()`, `->ignoreOnForm()`, `->onlyOnIndex()`, ...: assets use `ignoreOn*`, fields use `hideOn*` |
| `$assets->addJs('build/admin.js')` | `->addJsFile('build/admin.js')`, `->addCssFile()`, `->addWebpackEncoreEntry()`, `->addAssetMapperEntry()` |
