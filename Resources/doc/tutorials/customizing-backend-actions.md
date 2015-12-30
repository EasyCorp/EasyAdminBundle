Customizing Backend Actions
===========================

In this article you'll learn how to disable actions, how to tweak their
appearance and how to create your own custom actions. If you want to create your
own actions, read the [How to Define Custom Actions](custom-actions.md) tutorial.

Disable Actions for Some or All Entities
----------------------------------------

Use the `disabled_actions` option to define the name of the actions disabled
globally or for some entity. For example, to disable the `show` action for all
entities, define the following:

```yaml
easy_admin:
    disabled_actions: ['show']
    # ...
```

When an action is disabled, the backend no longer displays it in any of the
views. In this example, if you browse any entity listing, you'll no longer see
the `Show` link next to each item. Moreover, if you try to *hack* the URL to
access to the `Show` view of some entity, you'll see a *Forbidden Action Error*
page.

The `disabled_actions` option can also be defined for each entity. If you want
to disable the `new` action just for the `User` entity, use this configuration:

```yaml
easy_admin:
    entities:
        User:
            # ...
            disabled_actions: ['new']
```

Reload the backend and you'll no longer see the `Add User` button in the `list`
view of the entity. Again, if you try to *hack* the URL to add a new user,
you'll see the *Forbidden Action Error* page.

Beware that the values of the `disabled_actions` options are merged. If the
backend configuration is the following:

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

Besides disabling actions, you can also configure which actions are displayed
in each view and how do they look like. Again this configuration can be done
globally or per-entity.

### Adding or Removing Actions Globally

Define the actions to display using the `actions` option of each view:

```yaml
easy_admin:
    edit:
        actions: ['show']
    # ...
```

The value of the `actions` option is merged with the default action
configuration. This means that in the example above, the `edit` view of all
entities will include the `list`, `delete` and `show` actions (the first two
are the default actions and the last one is explicitly configured).

Instead of adding new actions, sometimes you want to remove them. To do so, use
the same `actions` option but prefix each action to remove with a dash (`-`):

```yaml
easy_admin:
    edit:
        actions: ['show', '-delete']
    # ...
```

In the example above, the `edit` view will now include just the `list` and the
`show` actions because of the following:

  * Default actions for `edit` view: `list` and `delete`
  * Actions added by configuration: `show`
  * Actions removed by configuration: `delete`
  * Resulting actions: `list` and `show`

### Adding or Removing Actions Per Entity

In addition to adding or removing actions for the entire backend, you can also
define the `actions` option for each entity:

```yaml
easy_admin:
    list:
        actions: ['-edit']
    entities:
        Customer:
            list:
                actions: ['-show']
            # ...
        Invoice:
            list:
                actions: ['edit']
            # ...
```

Entities inherit all the action configuration from the global backend. This
means that each entity starts with the same actions as the backend and then you
can add or remove any action.

In the example above, the actions of the `list` view for the `Customer` entity
will be the following:

  * Default actions for `list` view: `edit`, `list`, `new`, `search`, `show`
  * Actions added by the backend: none
  * Actions removed by the backend: `edit`
  * Resulting actions for the backend (and inherited by the entity): `list`,
    `new`, `search`, `show`
  * Actions added by the entity: none
  * Actions removed by the entity: `show`
  * Resulting actions for this entity: `list`, `new`, `search`

> **NOTE**
>
> Beware that the `actions` option just defines if an action should be
> displayed or not, but it doesn't disable the action. In the example above,
> if you *hack* the URL and change the `action` parameter manually, you can
> access to the `edit` and `show` actions. Use the `disabled_actions` options
> to ban those actions entirely.

Customizing the Actions Displayed in Each View
----------------------------------------------

In addition to adding or removing actions, you can also configure their
properties. To do so, you must use the expanded configuration format for the
customized action:

```yaml
easy_admin:
    list:
        actions: [{ name: 'edit', label: 'Modify' }]
```

When customizing lots of actions, consider using the alternative YAML syntax to
improve the readability of your backend configuration:

```yaml
easy_admin:
    list:
        actions:
            - { name: 'edit', label: 'Modify' }
            - { name: 'show', label: 'View details' }
```

The following properties can be configured for each action:

  * `name`, this is the only mandatory option. Later in this article you'll
    fully understand the importance of this option when defining your own
    custom actions.
  * `type`, (default value: `method`), this option defines the type of action,
    which can be `method` or `route`. Later in this article you'll fully
    understand the importance of this option when defining your own custom
    actions.
  * `label`, is the text displayed in the button or link associated with the
    action. If not defined, the action label is the *humanized* version of its
    `name` option.
  * `css_class`, is the CSS class or classes applied to the link or button used
    to render the action.
  * `icon`, is the name of the FontAwesome icon displayed next to the link or
    inside the button used to render the action. You don't have to include the
    `fa-` prefix of the icon name (e.g. to display the icon of a user, don't
    use `fa fa-user` or `fa-user`; just use `user`).
