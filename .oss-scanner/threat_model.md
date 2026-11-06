# Threat model

## What this project does and where untrusted input enters

EasyAdmin is a Symfony bundle to build admin backends for Doctrine ORM entities. A
developer installs it in their Symfony app and configures dashboards, CRUD controllers,
fields, filters and actions in PHP. The bundle then serves the index, detail, new and
edit pages, plus autocomplete, batch actions, search, sorting, filters and file uploads.

There are three kinds of actors:

- **The developer** writes the PHP config and Twig overrides. This is trusted code.
- **Backend users** are logged in to the admin. They are trusted only as far as the
  roles, `setPermission()`, `setEntityPermission()`, Symfony `access_control` rules and
  voters configured by the developer allow.
- **Visitors of the app** never use the admin, but the data they submit elsewhere (a
  sign-up form, a comment, an uploaded file) ends up in entities that backend users then
  view in EasyAdmin.

Untrusted input is:

- Everything in the request: query parameters (`crudAction`, `entityId`, `query`, `sort`,
  `filters`, `page`, autocomplete params, referrer and other URL params), form data,
  batch action payloads and uploaded files.
- Entity values, when they may come from visitors or from a backend user with fewer
  permissions than the one viewing them. This includes the string form of an entity
  (`__toString()`) whose fields come from such data.

## Trust model: what is by design

- Content written by the developer in PHP is rendered as HTML on purpose, so developers
  can format it: menu items, dashboard and page titles, field labels and help messages,
  action labels, flash messages built in their code, etc. Reporting that these are not
  escaped is not a vulnerability, unless EasyAdmin itself puts request or entity data in
  them.
- A callable configured by the developer that receives the entity (a custom page title,
  an action label, a `formatValue()` callback) is developer code. Its output is the
  developer's responsibility.
- Row-level authorization is the job of the app's voters and of `setEntityPermission()`.
  If the developer configured neither, any backend user who can open a CRUD controller can
  reach any entity of it by id. That is the documented behavior, not a missing check.
- A query builder set on an association field or filter scopes the choices shown in the
  user interface. It is not an authorization boundary.
- These options disable escaping and say so in their names; using them moves the risk to
  the developer: `TextField::renderAsHtml()`, `TextareaField::renderAsHtml()`,
  `AssociationField::renderAsHtml()`, `ChoiceField::escapeHtml(false)` and the
  `risky_inline_render` option of `FileUploadType`.

Everything else that lets a backend user or a visitor go beyond what the developer
configured is in scope.

## Components that matter most / least

- Most:
  - `src/Controller/AbstractCrudController.php` and `src/Controller/AbstractDashboardController.php`
    (every CRUD action, autocomplete, batch actions, and the boolean switch
    of the index page, which goes through the edit action)
  - `src/Security/` and every place that checks permissions before running an action,
    showing a field or returning an entity
  - `src/Router/` and `src/EventListener/` (how a request is mapped to a dashboard, a CRUD
    controller and an action, including custom actions and pretty URLs)
  - `src/Orm/` (search, sorting, filters and pagination query building, and the
    autocomplete response) and `src/Filter/`
  - `src/Field/Configurator/` (where field values get escaped before the templates)
  - `src/Form/Type/FileUploadType.php` and the file and image fields (upload paths, file
    names, how uploaded files are served)
  - `templates/` (any `|raw` that can receive request or entity data)
- Less: `src/Maker/`, `src/Command/`, `src/DataCollector/` (dev tools, they need
  console or profiler access), `src/Ai/`.
- Out of scope: `tests/`, `doc/`, `skills/`, `assets/` and the built files in `public/`,
  and bugs that live only in Symfony, Twig, Doctrine or other dependencies, unless
  EasyAdmin code makes them reachable.

## How to exercise it

- The image has the dependencies installed. Run the whole test suite with `make tests`,
  or a part of it with `make tests ARGS="tests/Functional/Security/"` or
  `make tests ARGS="--filter=testName"`. No database server is needed: the test apps
  use SQLite.
- Real Symfony apps that use the bundle live in `tests/Functional/Apps/`:
  `DefaultApp` (most CRUD features), `SecuredApp` (security, roles and permissions),
  `CustomizationApp` and `AdminRouteApp` (pretty URLs and admin routes). Their entities
  and fixtures are next to them.
- The preferred reproducer is a functional test in `tests/Functional/` that uses one of
  these apps, in the same style as the existing tests.

## How we rate severity

- Critical: code execution on the server, or access to the admin without being logged
  in.
- High: a backend user runs an action, reads or changes an entity, or sees a field that
  the configured permissions deny (`setPermission()`, `setEntityPermission()`,
  `access_control`, voters); stored XSS where data from a visitor or from a less
  privileged user runs script in another backend user's browser; writing files outside the
  configured upload directory.
- Medium: CSRF on an endpoint that changes data; reflected XSS that needs a backend user
  to open a crafted link; leaking data to a logged-in user who should not see it but
  without changing anything; open redirects.
- Low: denial of service and information leaks with no clear impact (versions, paths).
- Not a vulnerability: anything that needs a malicious developer config, a Twig override,
  one of the opt-ins listed above, or the Symfony `dev` environment (the profiler, debug
  pages).

## Anything to leave alone

Past audits traced these to their data source and found them safe. Don't report them
again unless you show a concrete path that the previous analysis missed:

- Stored XSS through text and textarea fields: values are escaped in the configurators
  before the template, and the `|raw` only lets EasyAdmin's own truncation markup through.
- Stored XSS through choice fields: what is rendered is the label configured by the
  developer; the entity value is only a lookup key.
- Reflected XSS through the search `query` parameter: Twig escapes it.
- XSS through autocomplete labels: escaped in PHP when building the response and in the
  JavaScript widget, unless the developer opted into HTML.
- SQL or DQL injection through search, sort or filters: search values are bound as
  parameters, sort properties are checked against real Doctrine fields and the direction
  is restricted.
- Insecure deserialization: request data is never unserialized or evaluated.

When you report an issue, include the affected EasyAdmin version or commit, the minimal
config needed (CRUD controller and field config), the request that triggers it and, if
you can, a patch with a functional test.
