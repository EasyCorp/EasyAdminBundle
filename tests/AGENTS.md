# Testing Guidelines

Conventions for writing and changing tests in EasyAdminBundle. The root `AGENTS.md` covers everything else.

## Layout

```
tests/
├── Unit/         # Isolated tests — extend PHPUnit\Framework\TestCase, no Symfony container and no controllers
├── Functional/   # Integration tests — extend WebTestCase, boot a real test app
│   └── Apps/     # Real Symfony applications used by the functional tests
└── TestUtils/    # Meta-tests for the test helper traits in src/Test/
```

Functional tests are grouped by feature (`Fields/`, `Filters/`, `Actions/`, `Customization/`, `Security/`, …), not by `src/` directory.

## Test applications (`tests/Functional/Apps/`)

Each is a real, self-contained Symfony app with its own Kernel, config, controllers, and entities. Reuse the closest existing app — add entities/controllers to it rather than creating a new app unless a feature genuinely needs isolation.

- **DefaultApp** — main app; most functional tests use it to test the EasyAdmin defaults in an unconfigured app
- **CustomizationApp** — theme/template/customization tests
- **SecuredApp** — security and permission tests
- **AdminRouteApp** — routing and i18n (ships a separate `I18nKernel.php`)

Write tests against the blog entities of each app (`BlogPost`, `Category` and `User` in DefaultApp; `BlogPost` and `Category` in CustomizationApp) so they're easy to read. If a test needs a column or relation they don't have, add it to them. Only add an entity to `Entity/Synthetic/` when the test needs a shape the blog can't have (`CompositeKeyTestEntity`, `ConstructorArgsTestEntity`) or a catalog of one column per type (`FieldTestEntity`, `FilterTestEntity`, `FormTestEntity`, `DoctrineTypesTestEntity`).

Put CRUD controllers in a folder named after the feature they test (`Controller/Actions/`, `Controller/Sort/`, …) and keep `Controller/Synthetic/` for the synthetic entities. EasyAdmin builds route names from the short class name, so two controllers with the same short name in different namespaces fail unless one of them sets its route name with `#[AdminRoute]`.

## Base classes and helpers (`src/Test/`)

These are **public, stable API** — bundle users rely on them too, so don't break them.

- Extend **`AbstractCrudTestCase`** for CRUD controller tests. It extends `WebTestCase`, composes the `CrudTest*` traits, and exposes `$this->client`, `$this->entityManager`, and `$this->adminUrlGenerator`. Subclasses implement `getControllerFqcn()` and `getDashboardFqcn()`.
- Extend **`tests/Functional/AbstractFieldFunctionalTestCase.php`** for tests of individual fields (it builds on `AbstractCrudTestCase`).
- Extend **`WebTestCase`** directly for simpler functional tests (e.g. dashboards).
- Extend **`PHPUnit\Framework\TestCase`** for unit tests.

Traits in `src/Test/Trait/`:
- **`CrudTestUrlGeneration`** — build admin URLs (`generateIndexUrl()`, `generateDetailUrl()`, `generateNewFormUrl()`, …)
- **`CrudTestActions`** — perform requests/actions through the client
- **`CrudTestIndexAsserts`**, **`CrudTestFormAsserts`** — DOM assertions for index/form pages
- **`CrudTestSelectors`** — shared CSS selectors

## Core rules

### Tests must be real — avoid mocks
Do **not** use mocks unless strictly justified. Mocks make tests non-real and add a lot of complexity. Prefer real objects, the real container, and the real database provided by the test apps. A mock is the explicitly-justified exception, never the default.

### Functional tests go through the HTTP layer
Never invoke a controller method directly in PHP. Browse the controller's URL with the test client (`$this->client->request(...)` or the `CrudTestUrlGeneration` helpers) and assert on the response/DOM. Testing a controller by calling its method directly is not allowed.

## Fixtures

- Every test starts with empty tables and creates only the data it needs with the Foundry factories in `tests/Functional/Apps/<App>/src/Factory/` (`DefaultApp`, also used by `SecuredApp`, and `CustomizationApp`). There are no shared fixtures.
- DAMA DoctrineTestBundle rolls back each test's transaction, so don't clean up rows in `tearDown()`.
- Factories only fill the required columns. Set explicitly every value a test asserts, and never assert on a factory default.
- Never hard-code ids or counts: use the ids of the objects you created and count the rows you created. Create rows in the order the test asserts them.
- Auto-refresh is disabled because it needs PHP 8.4, so objects aren't refreshed after a request. To check what a request changed, clear the entity manager and find the row again by id.
- Use simple, unrealistic data (`'Action 1'`, `'Field 1'`, …), not lifelike values.

## Conventions

- Add a `void` return type to every test method.
- Name tests descriptively; don't duplicate the `test` prefix inside the name.
- Use the `#[DataProvider]` / `#[TestWith]` attributes to avoid duplicated tests.
- Properties before methods; `setUp()`/`tearDown()` right after any constructor.

## Running tests

`make tests` accepts an optional `ARGS` to scope the run, while keeping the test cache reset:

```bash
make tests                              # full suite
make tests ARGS="tests/Unit/Field/"     # one directory
make tests ARGS="--filter=testFoo"      # one test
make tests ARGS="tests/Unit --filter=testFoo"  # combined
```

Prefer this over a raw `vendor/bin/phpunit` run, which skips the cache reset.

`make tests-parallel` runs the suite with ParaTest. The main process compiles the test app containers once and the workers share them, but each worker gets its own SQLite database (its path includes ParaTest's `TEST_TOKEN`). Tests must not share other files: use random names for temporary directories, not `uniqid()`.
