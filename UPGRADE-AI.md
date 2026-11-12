AI Upgrade Guide
================

This guide is for AI agents. If you are a human, read [UPGRADE.md](UPGRADE.md) instead.

## EasyAdmin 5.10.1

Use this prompt to upgrade backends for the string callable and route option changes made in EasyAdmin 5.10.1.

<upgrade-prompt version="5.10.1">

EasyAdmin 5.10.1 never calls strings in the options that accept a string or a
callable: a string is always the literal value (a title, a URL, a badge content, a
file name pattern or the name of a method of the collection entry). It also applies
the `options` argument of `#[AdminRoute]` (`host`, `schemes`, `condition`,
`requirements`, `defaults`, `locale`, `format`, `utf8`, `stateless`, `options`) to
the routes of CRUD controllers. Before, CRUD controllers ignored all of them except
the `methods` option of actions. Search the PHP code of the project and apply what's
relevant. Skip what doesn't apply.

## Required changes

1. Find calls to these methods whose argument is a string with the name of a
   function (`'my_function'`) or of a static method (`'App\Util\Titles::build'`)
   that was expected to be called:

   - `setPageTitle()` (second argument)
   - `linkToUrl()`
   - `setBadge()` after `FormField::addTab()` (first argument)
   - `setEntryToStringMethod()` of `CollectionField`
   - `setUploadedFileNamePattern()` of `FileField` and `ImageField`
   - the `upload_filename` option of `FileUploadType`

   Replace the string with a closure or a first-class callable:

   ```php
   // before
   $crud->setPageTitle(Crud::PAGE_DETAIL, 'App\Util\Titles::build');
   $action->linkToUrl('build_invoice_url');

   // after
   $crud->setPageTitle(Crud::PAGE_DETAIL, Titles::build(...));
   $action->linkToUrl(build_invoice_url(...));
   ```

   Don't change strings that are literal values (`'Products'`, `'https://example.com'`)
   or, in `setEntryToStringMethod()`, names of methods of the entry (`'getFullName'`).

2. Find `#[AdminRoute(` attributes with an `options:` argument applied to classes
   that extend `AbstractCrudController` (or implement `CrudControllerInterface`) and
   to their methods. Their options now restrict the generated routes: the class
   options apply to all the routes of the controller and the method options override
   the class options with the same name. Requests that don't match them return a 404
   error. Keep the options that describe a wanted restriction and remove the others:

   ```php
   // before: 'host' was ignored and the routes worked on every host
   #[AdminRoute(options: ['host' => 'intranet.example.com'])]
   class ProductCrudController extends AbstractCrudController

   // after, to keep the routes working on every host
   class ProductCrudController extends AbstractCrudController
   ```

## Optional changes

1. Find `#[AdminRoute(` attributes with `'methods'` inside `options:` applied to
   classes that extend `AbstractCrudController`. That option is ignored there (as
   before). Remove it from the class attribute. Add it to the `#[AdminRoute]`
   attribute of a custom action only when that action must be limited to those
   HTTP methods:

   ```php
   // before
   #[AdminRoute(options: ['methods' => ['POST']])]
   class ProductCrudController extends AbstractCrudController
   {
       #[AdminRoute('/{id}/publish', name: 'publish')]
       public function publish(Product $product): Response

   // after
   class ProductCrudController extends AbstractCrudController
   {
       #[AdminRoute('/{id}/publish', name: 'publish', options: ['methods' => ['POST']])]
       public function publish(Product $product): Response
   ```

</upgrade-prompt>

## EasyAdmin 5.10.0

Use this prompt to upgrade backends for the flag and security changes made in EasyAdmin 5.10.0.

<upgrade-prompt version="5.10.0">

EasyAdmin 5.10.0 changes the look of the `<twig:ea:Flag>` component and checks
`Actions::setPermission()` and `Actions::disable()` also when running custom action
methods (the ones used in `linkToCrudAction()`, or in `linkToRoute()` with a custom
action route of the same CRUD controller). Opening the URL of a disabled or not
granted action now returns a 403 error. Search the project (templates, CSS, PHP) and
apply what's relevant. Skip what doesn't apply.

## Required changes

1. Find custom action methods used by several actions (for example, the same
   `linkToCrudAction('renderInvoice')` on two pages). Users can run the method when
   one of those actions is enabled and granted, and an action without permission lets
   everyone run it. Set the same permission on all those actions, or add
   `#[IsGranted('ROLE_...')]` to the method.

2. Find code that opens the URL of a custom action that is disabled or has a
   permission (e.g. links built with `ea_url()` or `AdminUrlGenerator` in templates or
   controllers). Those URLs now return a 403 error: enable the action, grant its
   permission, or remove the link.

3. `<twig:ea:Flag>` now renders flags with rounded corners and an outline also outside
   EasyAdmin. Where a flag must keep the old look (square and without outline), add
   these props:

   ```twig
   {# before #} <twig:ea:Flag countryCode="JP"/>
   {# after #}  <twig:ea:Flag countryCode="JP" radius="none" bordered="{{ false }}"/>
   ```

4. With `showName`, the country name is now wrapped in a
   `<span class="country-flag-name">` element. Update the CSS that styled that name.

5. The `--field-country-flag-border-color` CSS variable was removed (it wasn't used).
   Remove it, or use `--flag-outline-color` instead.

## Optional changes

1. Remove the permission checks inside custom action methods that repeat the
   permission of their action, because EasyAdmin checks it now:

   ```php
   // before
   $actions->setPermission('publish', 'ROLE_EDITOR');
   // ...
   public function publish(Product $product): Response
   {
       $this->denyAccessUnlessGranted('ROLE_EDITOR');
       // ...
   }

   // after: remove the denyAccessUnlessGranted() call
   ```

   Keep the checks of actions that use `linkToUrl()` or routes that aren't custom
   actions of the same CRUD controller: EasyAdmin doesn't check those URLs.

2. Replace hand-written URLs like `/admin?routeName=admin_product_publish` with the
   URL of the admin route (generated with `path()` or `ea_url()`). The old URLs still work,
   but now redirect with a `307` status code.

3. Replace custom CSS that rounds or outlines flags with the `radius` (`none`, `sm`,
   `md`, `lg`, `full`) and `bordered` props of `<twig:ea:Flag>`, or with the
   `--flag-radius` and `--flag-outline-color` CSS variables.

</upgrade-prompt>

## EasyAdmin 5.9.0

Use this prompt to upgrade backends for the design changes made in EasyAdmin 5.9.0.

<upgrade-prompt version="5.9.0">

EasyAdmin 5.9.0 redesigns the interface. Changes in HTML, IDs and CSS classes are
minimal, but this backend may need some adjustments. Search the project (templates,
CSS, JS, PHP) and apply what's relevant. Skip what doesn't apply.

## Required changes

1. Custom pages must extend the content template. It adds the CSS class that enables
   the default typography. All layout blocks keep working. If you override
   `body_class`, keep `{{ parent() }}`.

   ```twig
   {# before #} {% extends '@EasyAdmin/layout.html.twig' %}
   {# after #}  {% extends '@EasyAdmin/page/content.html.twig' %}
   ```

2. Replace buttons, badges, alerts, etc. styled with custom HTML/CSS with the bundled
   Twig components, so they match the new design. Available: `Button`, `Badge`,
   `Alert`, `ActionMenu`, `Modal`, `Tabs`, `Pagination`, `Switch`, `Icon`, `Flag`.

   ```twig
   <twig:ea:Button color="primary" htmlElement="a" href="{{ url }}">Save</twig:ea:Button>
   <twig:ea:Badge color="success">Active</twig:ea:Badge>
   ```

3. Fix deprecated button APIs. Buttons now have a `color` (`default`, `primary`,
   `success`, `warning`, `danger`, `info`) and a `variant` (`solid`, `outline`, `light`,
   `ghost`, `link`).
   - `<twig:ea:Button variant="primary">` -> `color="primary"`
   - `isInvisible` -> `variant="ghost"`
   - `ButtonVariant::Primary` and the other color cases -> `ButtonColor::Primary`, etc.
   - `ActionDto`/`ActionGroupDto`: `getVariant()` -> `getColor()` and
     `getButtonVariant()`; `setVariant(<color>)` -> `setColor()`
   - PHP subclasses of `Button` that override `mount()` must add the new
     `string|ButtonColor|null $color = null` last argument.

4. Update overridden templates:
   - `crud/action.html.twig`, `crud/action_group.html.twig`: pass `action.color` and
     `action.buttonVariant` to the button, not `action.variant`.
   - `content_help` block: it now renders after `<section class="content-header">`, not
     inside the `<h1>`. If a `content_header` override renders `block('content_help')`,
     remove that call or the help displays twice.
   - `crud/index.html.twig`, `page_actions` block: it now wraps its contents in
     `.page-actions-stack`; re-sync your copy.

5. Forms that copy old EasyAdmin markup by hand (`form-panel`, `field-form_panel`, etc.)
   lose their styles. Use the markup of the current fieldsets:

   ```twig
   <div class="form-fieldset field-form_fieldset">
       <fieldset>
           <div class="form-fieldset-header">
               <div class="form-fieldset-title">
                   <span class="not-collapsible form-fieldset-title-content">Title</span>
               </div>
           </div>
           <div class="form-fieldset-body">
               <div class="row">{# fields #}</div>
           </div>
       </fieldset>
   </div>
   ```

6. Update CSS and JS that use removed things:
   - `--button-{color}-bg`, `-hover-bg`, `-active-bg`, `-border-color`, `-box-shadow`,
     `-icon-color`, `--button-invisible-*`, `--button-outline-primary-color` ->
     `--button-{color}-tone`, `-solid-color`, `-color`, `-outline-color`, `-light-bg`,
     `-light-hover-bg`, `-outline-border-color`
   - `#sidebar-resizer-handler`, `#content-resizer-handler` ->
     `button.ea-layout-toggle-sidebar`, `button.ea-layout-toggle-content`
   - `.batch-actions` is now hidden with CSS and `inert`, not with `d-none`
   - `.dropstart` in submenus -> `.dropend`
   - `.content-header-help` and `.ea-content-help-popover` no longer exist in the page
     header; the page help is now `.content-help`
   - Selectors like `.action-edit` now also match actions inside action groups; check
     they don't match by mistake. The toggle of a split button keeps the custom CSS
     classes of the main action, but not its `action-{name}` class.
   - `.btn-light` now means the light variant (a tinted button when combined with a
     color class).

7. These CSS variables are still defined, but no style reads them, so overriding them
   has no effect. Replace those overrides with what's now in use (see the variables in
   "Optional changes"), or remove them: `--resize-handler-width`,
   `--resize-handler-hover-bg`, `--content-top-border-color`,
   `--dropdown-link-disabled-color`, `--dropdown-settings-active-item-*`,
   `--pagination-hover-border-color`, `--form-type-text-editor-dialog-box-shadow`,
   `--form-fieldset-header-border-color`, `--link-danger-color`,
   `--link-danger-hover-color`, `--popover-bg`, `--popover-border-color`,
   `--popover-color`, `--popover-max-width`.

8. `ActionMenu` menus now open towards the end side (to the right). Add
   `align="end"` to the `ActionList` of any menu placed at the right edge of the page or
   a table.

9. In custom modals, the title and description styles only apply to the `h4` and `p`
   elements written directly in the modal body, not to nested ones.

## Optional changes (better look)

1. Remove custom typography CSS (headings, paragraphs, lists, tables, links, code) from
   custom pages. The new default styles cover it, and unlayered custom CSS always wins
   over them. Keep only CSS for elements unique to the page. Tune the defaults with
   `--ea-prose-font-size` and `--ea-prose-line-height`; links use `--prose-link-color`.
   For content that shouldn't get these styles, wrap it in `<div data-ea-not-prose>`.

2. Remove or rewrite CSS that restyles form fields, fieldsets, dropdowns, buttons, the
   pagination and the datagrid header; the new design may already solve it. Prefer CSS
   variables over overrides, and replace hardcoded colors with variables like
   `--text-color`, `--text-muted` and `--ea-primary`, so dark mode keeps working.
   - Form controls: `--form-control-height`, `--form-control-border-radius`,
     `--form-control-padding-x`, `--form-control-padding-y`,
     `--form-control-disabled-opacity`, `--form-input-focus-border-color`,
     `--form-focus-ring-color`, `--form-input-error-ring-color`,
     `--form-input-placeholder-color`
   - Fieldsets (now cards in all forms): `--form-fieldset-frame-bg`,
     `--form-fieldset-frame-hover-bg`, `--form-fieldset-panel-bg`,
     `--form-fieldset-panel-border-color`, `--form-fieldset-panel-shadow`
   - Dropdowns: `--dropdown-ring-color`, `--dropdown-header-color`
   - Replace these deprecated variables: `--form-input-shadow`,
     `--form-input-hover-shadow`, `--form-input-hover-border-color`,
     `--form-input-error-shadow`, `--form-help-active-color`,
     `--form-type-check-input-box-shadow`, `--form-type-autocomplete-*`

3. Review CSS that depends on old values:
   - The palette (`--blue-600`, `--red-500`, etc.) now uses the Tailwind CSS 4 values
     in `oklch()`, and `--color-info` is cyan.
   - Regular text uses a `435` font weight (`--font-weight-normal`); headings use `600`
     (`--heading-font-weight`); the page title is `24px` on all screens.
   - Each bundled font defines its own `--font-weight-*` values, and all fonts are
     scaled with `--font-size-adjust`. Use `--font-family-system-sans-serif` and
     `--font-family-system-monospace` for the old system font stacks.

4. Use `asPrimaryAction()`, `asDangerAction()`, etc. instead of
   `addCssClass('btn btn-primary')`. Default actions are outline buttons, primary ones
   solid, other colors light, and `asTextLink()` ones ghost.

5. Remove the `showBlankIcons` prop and calls to
   `ActionGroupDto::hasAnyActionWithIcon()`. Menus now align items without icons
   automatically.

6. Review `Crud::setHelp()` messages. Only the first line is visible until the user
   expands it, so start with a sentence that works on its own.

7. Review fields with the `readonly` attribute: they now look like plain text. Use
   `disabled` if the field must still look like a field. Disabled fields are the
   editable ones dimmed.

8. Review `primary` badges and alerts, and the `true` value of boolean badges: they're
   now blue and no longer use the theme color. Change the color if they were meant to
   match the brand.

9. Form errors no longer color the label. To restore red labels, set
   `--form-input-error-legend-color` to `var(--form-input-error-border-color)`.

10. If the backend relied on the old look, set it explicitly: the default color is now
    neutral (almost black), the grays are neutral and the default font is Inter. Links
    now use the text color.

    ```php
    ->setTheme(Theme::new()
        ->primaryColor('hsl(230, 61%, 58%)', dark: 'hsl(224, 84%, 49%)')
        ->grays(GrayScale::SLATE, dark: GrayScale::NEUTRAL)
        ->radius('xs')
        ->font(base: Font::System, monospace: Font::System))
    ```

    ```css
    :root, .ea-dark-scheme {
        --link-color: var(--ea-primary-text);
        --link-hover-color: var(--ea-primary-strong);
    }
    ```

</upgrade-prompt>
