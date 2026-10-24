Content Lock
============

.. note::

    This feature requires :doc:`EasyAdmin Pro </pro>`, a paid extension
    to EasyAdmin.

When two people open the same edit form, one can accidentally overwrite
the other's changes. Content Lock avoids this by letting one person edit
an entity at a time. Anyone else sees who is editing it and waits for the
form to become available.

Enable the feature and choose the entities to protect. You don't need to
change your CRUD controllers.

Enabling Content Locking
------------------------

Content locking is bundled with EasyAdmin Pro but disabled by default.
Enable it in your application configuration:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        content_lock:
            enabled: true

Generate a migration, review it, and apply it to create the lock table:

.. code-block:: terminal

    $ php bin/console make:migration
    $ php bin/console doctrine:migrations:migrate

See :doc:`EasyAdmin Pro installation </pro>` to change the database
connection or table name.
Locking requires a logged-in user; anonymous edit pages aren't locked.

How Locking Works
-----------------

A lock is acquired when someone opens an edit page. While the form stays
open, the browser refreshes it every 30 seconds. Saving or leaving the
page releases the lock. If the browser crashes or loses its connection,
the lock expires after 15 minutes without a refresh.

Anyone waiting on the locked page is taken to the edit form automatically
when the lock is released or expires. They can also return to the index,
or use "Edit anyway" if they have permission (see `Overriding a Lock`_).

To change the timing, set these values in seconds:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        content_lock:
            lock_ttl_seconds: 900
            heartbeat_interval_seconds: 30

The refresh interval must be at least 10 seconds and shorter than the
lock lifetime, which must be at least 60 seconds.

.. tip::

    A shorter lifetime frees abandoned forms sooner, but also makes locks
    more likely to expire during a temporary connection problem.

Overriding a Lock
-----------------

The "Edit anyway" button lets an authorized user take over a lock. By
default, they must confirm first. Set ``confirm_override: false`` to skip
that confirmation.

To take over someone else's lock, a user must be granted **every role**
the owner had when the lock was acquired. Symfony's role hierarchy and
voters apply. For example, an administrator can take over an editor's lock
if they inherit all the editor's roles. A lock with no recorded roles
can't be overridden by another user.

The previous editor sees a warning on the next refresh, and their save
buttons are disabled. The server also rejects stale submissions, so they
can't overwrite the new editor's changes. If their lock expires after a
connection problem, they see a warning asking them to copy their changes
before continuing.

Choosing Which Entities Are Locked
----------------------------------

By default, content locking applies to every entity managed by
EasyAdmin (``included_entities: '*'``). To lock only specific entities,
list their fully qualified class names:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        content_lock:
            enabled: true
            included_entities:
                - 'App\Entity\Invoice'
                - 'App\Entity\Contract'

When ``included_entities`` is set to ``'*'``, you can still exclude
individual entities with ``excluded_entities``. Excluded entities are
never locked, even if they appear in ``included_entities``:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        content_lock:
            enabled: true
            included_entities: '*'
            excluded_entities:
                - 'App\Entity\Draft'

Displaying the Lock Owner
-------------------------

The ``lock_owner_display.default`` option controls how much information
about the lock owner is shown on the locked page:

* ``identifier`` (default): the locked page shows the name of the
  person editing the entity. If your user class implements
  ``__toString()``, its return value is used; otherwise the user
  identifier (for example, the email or username) is shown.
* ``anonymous``: the locked page reports that the entity is being
  edited by someone else, without revealing who.

The same setting applies to the banner shown to an editor whose lock has
been overridden (see `Overriding a Lock`_).

If your application has several dashboards with different audiences,
list the exceptions under ``lock_owner_display.per_dashboard``, a map of
dashboard controller class names to display modes. This is useful when,
for example, internal staff may see who is editing an entity, but
external users should not learn the identities of your employees:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        content_lock:
            enabled: true
            lock_owner_display:
                default: 'identifier'
                per_dashboard:
                    'App\Controller\Admin\PartnerDashboardController': 'anonymous'

Other dashboards use the ``default`` display mode.

Configuration Reference
-----------------------

These are the available options and their defaults:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        content_lock:
            # Enable or disable content locking globally.
            enabled: false

            # DBAL connection name. When null, the global
            # easyadmin_pro.dbal_connection value is used.
            dbal_connection: null

            # The database table name for content lock entries.
            table_name: easyadmin_content_locks

            # Lock time-to-live in seconds (minimum: 60).
            lock_ttl_seconds: 900

            # Heartbeat interval in seconds (minimum: 10; must be
            # strictly less than lock_ttl_seconds).
            heartbeat_interval_seconds: 30

            # How to display the lock owner.
            lock_owner_display:
                # Either "identifier" or "anonymous". Used by every
                # dashboard without a "per_dashboard" override.
                default: identifier

                # Map of dashboard FQCNs to either display mode.
                per_dashboard: []

            # Whether to show a confirmation modal before overriding.
            confirm_override: true

            # Either "*" to lock every entity, or an array of
            # entity FQCNs to lock.
            included_entities: '*'

            # Array of entity FQCNs to exclude from locking.
            excluded_entities: []

Storage Errors
--------------

If the lock table is missing, editing stops with an error asking you to
create it. Run the migrations from `Enabling Content Locking`_. For other
errors when acquiring a lock, the bundle logs the error and lets the user
edit without locking.
