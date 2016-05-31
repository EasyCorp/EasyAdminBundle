Chapter 5. Actions Configuration
================================

EasyAdmin backends consist of **views** and **actions**. The **view** is the
page where you are (`list`, `edit`, `show`, etc.) and the **action** is what
you do on that page (`search`, `delete`, etc.)

Views include one or more **actions** to perform operations on the items
displayed in that view. For example, the default `list` view interface includes
four actions as buttons or links:

![List view interface](../images/easyadmin-list-view-actions.png)

These are the built-in actions included by default in each view:

| View   | Default Actions
| ------ | -----------------------------------------
| `list` | `delete`, `edit`, `list`, `new`, `search`
| `edit` | `delete`, `list`
| `new`  | `list`
| `show` | `delete`, `edit`, `list`

In this chapter you'll learn how to enable, disable and customize these built-in
actions. If you want to create your own actions read the
[How to Define Custom Actions][1] tutorial.

Disable Actions for Some or All Entities
----------------------------------------

Use the `disabled_actions` option to disable any action globally or just for
some entity. For example, to disable the `show` action for all entities:

```yaml
easy_admin:
    disabled_actions: ['show']
    # ...
```

When an action is disabled, the backend no longer displays it in any of the
views. Moreover, if some user tries to *hack* the URL to access to a disabled
action, they'll see a *Forbidden Action* error page.

The `disabled_actions` option can also be defined per entity. For example, to
disable the `new` action just for the `User` entity:

```yaml
easy_admin:
    entities:
        User:
            # ...
            disabled_actions: ['new']
```

The values of the `disabled_actions` options are merged in cascade for each
entity. If the backend configuration is the following:

```yaml
easy_admin:
    disabled_actions: ['show']
    # ...
    entities:
        User:
            # ...
            disabled_actions: ['new']
```

The `User` entity will have both the `new` and the `show` actions disabled.

Configure the Actions Displayed in Each View
--------------------------------------------

### Adding Actions Globally

Define the actions to display on each backend view using the `actions` option:

```yaml
easy_admin:
    edit:
        actions: ['show']
    list:
        actions: ['edit', 'search']
    # ...
```

The value of the `actions` option is merged with the default action
configuration for each view. In the above example, the `edit` view of all
entities will include the `list`, `delete` and `show` actions (the first two are
the default actions and the last one is explicitly configured).

### Removing Actions Globally

Instead of adding new actions, sometimes you want to remove them. To do so, use
the same `actions` option but prefix each action name with a dash (`-`):

```yaml
easy_admin:
    edit:
        actions: ['show', '-delete']
    list:
        actions: ['edit', 'search', '-delete']
    # ...
```

In the above example, the `edit` view will now include just the `list` and the
`show` actions because of the following configuration merge:

| Configuration merge                | Actions
| ---------------------------------- | -------------------------------
| Default actions                    | `list`, `delete`
| Actions added by global config     | `show`
| Actions deleted by global config   | `delete`
| **Resulting actions for entities** | `list`, `show`

### Adding Actions Per Entity

Define the actions to add to each entity view using the `actions` option:

```yaml
easy_admin:
    entities:
        Invoice:
            list:
                actions: ['show']
            # ...
```

The global `action` configuration (if any) is merged with the entity's `action`
configuration, as explained in the next section.

### Removing Actions Per Entity

Define the actions to remove to each entity view using the `actions` option and
prefixing the action name with a dash (`-`):

```yaml
easy_admin:
    entities:
        Customer:
            list:
                actions: ['-search']
            # ...
```

The global `action` configuration (if any) is merged in cascade with the
entity's `action` configuration. Consider the following example:

```yaml
easy_admin:
    list:
        actions: ['-edit']
    entities:
        Customer:
            list:
                actions: ['-search']
            # ...
        Invoice:
            list:
                actions: ['edit']
            # ...
```

This would be the configuration merging process for the `Invoice` entity:

| Configuration merge              | Actions
| -------------------------------- | -------------------------------
| Default actions                  | `edit`, `new`, `search`, `show`
| Actions added by global config   | (none)
| Actions deleted by global config | `edit`
| Actions added by entity          | `edit`
| Actions removed by entity        | (none)
| **Resulting actions for entity** | `edit`, `new`, `search`, `show`

This would be the configuration merging process for the `Customer` entity:

| Configuration merge              | Actions
| -------------------------------- | -------------------------------
| Default actions                  | `edit`, `new`, `search`, `show`
| Actions added by global config   | (none)
| Actions deleted by global config | `edit`
| Actions added by entity          | (none)
| Actions removed by entity        | `search`
| **Resulting actions for entity** | `new`, `show`

> **NOTE**
>
> Beware that the `actions` option just defines if an action should be
> displayed or not, but it doesn't disable the action (a malicious user could
> *hack* the URL and change the `action` parameter manually). Use the
> `disabled_actions` options to ban those actions entirely.

Customizing the Actions Displayed in Each View
----------------------------------------------

In addition to adding or removing actions, you can also configure their
properties, such as their labels and icons. To do so, you must use the expanded
configuration format:

```yaml
easy_admin:
    list:
        # shortcut configuration format
        actions: ['edit']

        # expanded configuration format
        actions:
            - { name: 'edit' }
```

Then, define any of the following options to customize the action:

  * `name`, this is the only mandatory option. You can freely choose this value,
    but it must be valid as a PHP method name (use characters, numbers and
    underscores only).
  * `label`, is the text displayed in the button or link associated with the
    action. If not defined, the action label is the *humanized* version of its
    `name` option.
  * `css_class`, is the CSS class or classes applied to the link or button used
    to render the action.
  * `icon`, is the name of the FontAwesome icon displayed next to the link or
    inside the button used to render the action. You don't have to include the
    `fa-` prefix of the icon name (e.g. to display the icon of a user, don't
    use `fa fa-user` or `fa-user`; just use `user`).

-------------------------------------------------------------------------------

&larr; [Chapter 4. Edit and New Views Configuration](4-edit-new-configuration.md)  |  [Chapter 6. Menu Configuration](6-menu-configuration.md) &rarr;

[1]: ../tutorials/custom-actions.md
