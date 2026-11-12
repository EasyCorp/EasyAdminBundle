# Errors and fixes

Messages that EasyAdmin 5 (or PHPStan) shows for the most common mistakes.
Search for a fragment of the message you got.

| Message contains | Fix |
|---|---|
| `must apply the #[AdminDashboard] attribute` | Add `#[AdminDashboard(routePath: '/admin', routeName: 'admin')]` to the dashboard class. |
| `missing either the "routePath" or "routeName" arguments` | Pass both arguments to `#[AdminDashboard]`. |
| `is missing the #[AdminRoute] attribute` | Add `#[AdminRoute('/{id}/<action>', name: '<action>')]` to the controller method named in the message. |
| `is missing the "{id}" or "{entityId}" placeholder` | Add `{id}` to the customized route path of that built-in `edit`, `detail` or `delete` action. |
| `the only allowed HTTP methods are` | Use only GET, POST, PUT, PATCH, DELETE, HEAD in `options: ['methods' => [...]]`. |
| `would create an admin route called "..." which already exists` | Change the `name` argument of one of the two `#[AdminRoute]` attributes. |
| `cannot define both "allowedControllers" and "deniedControllers"` | Keep one of the two `#[AdminDashboard]` arguments. |
| `You must define the currency for the` | Add `setCurrency('EUR')` or `setCurrencyPropertyPath()` to that `MoneyField`. |
| `Batch actions can be added only to the "index" page` | Add the batch action with `addBatchAction()`, not with `add(Crud::PAGE_DETAIL, ...)`. |
| `InsufficientEntityPermissionException` | Expected: the user fails `is_granted()` for that entity under `setEntityPermission()`. Log in with the right role or change the voter. |
| `Class ... extends generic class ... but does not specify its types` (PHPStan) | Add `/** @extends AbstractCrudController<Entity> */`. |
