Messenger Inspector
===================

.. note::

    This feature requires :doc:`EasyAdmin Pro </pro>`, a paid extension
    to EasyAdmin.

The Messenger Inspector lets you check your `Symfony Messenger`_ queues
from EasyAdmin. Find failed messages, see what went wrong, and retry or
remove them without leaving your backend.

It works with any Messenger transport, including Doctrine, AMQP, Redis,
Amazon SQS and ``sync``. Messages must pass through a transport to appear;
messages handled directly by the bus without transport routing aren't
recorded.

Enabling the Messenger Inspector
--------------------------------

The inspector requires Symfony Messenger. If you haven't installed it yet:

.. code-block:: terminal

    $ composer require symfony/messenger

Enable the inspector in your application:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            enabled: true

Then generate a migration, review it, and apply it:

.. code-block:: terminal

    $ php bin/console make:migration
    $ php bin/console doctrine:migrations:migrate

The inspector stores its own message history. See
:doc:`EasyAdmin Pro installation </pro>` to change its database connection
or table name.

By default, errors writing inspector data are logged without interrupting
message processing. Set ``fail_on_storage_error: true`` if you want those
errors to propagate, for example while debugging your setup.

Displaying the Inspector in Your Backend
----------------------------------------

Create a controller that extends ``AbstractMessengerInspectorController``.
You choose its URL and protect it with your backend's access control rules.

The ``#[AdminRoute]`` attribute places the page under your dashboard URL.
This example requires ``ROLE_ADMIN`` to open it::

    // src/Controller/Admin/MessengerInspectorController.php
    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    use EasyCorp\Bundle\EasyAdminProBundle\MessengerInspector\Config\MessengerInspector;
    use EasyCorp\Bundle\EasyAdminProBundle\MessengerInspector\Controller\AbstractMessengerInspectorController;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[AdminRoute(path: '/messenger-inspector', name: 'messenger_inspector')]
    #[IsGranted('ROLE_ADMIN')]
    final class MessengerInspectorController extends AbstractMessengerInspectorController
    {
        public function configureMessengerInspector(): MessengerInspector
        {
            return MessengerInspector::new()
                ->setPageSize(50)
                ->enableAutoRefresh();
        }
    }

Add a link in your dashboard's ``configureMenuItems()`` method. For a
dashboard whose route name is ``admin``, the route above is named
``admin_messenger_inspector``::

    use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

    yield MenuItem::linkToRoute(
        'Messenger',
        'fa fa-envelope',
        'admin_messenger_inspector',
    );

.. note::

    ``#[AdminRoute]`` requires EasyAdmin pretty admin routes. If you don't
    use them, replace it with Symfony's ``#[Route]`` attribute and use the
    same route name in the menu::

        use Symfony\Component\Routing\Attribute\Route;

        #[Route('/admin/messenger-inspector', name: 'admin_messenger_inspector')]

The Interface
~~~~~~~~~~~~~

The list starts with messages that need attention: failed, retrying and
stale messages. Change the status filter to see other messages, or search
by class, description, error message or transport. The date filter narrows
the results further. Turn on auto-refresh to follow changes as they happen.

Click a message to open its details, including attempts, timings, errors
and captured payload. The interface labels ``sent`` messages as *Queued*
and ``handled`` messages as *Succeeded*.

For failed messages, you can:

* **Retry**: send the message through the bus again. Its row returns to
  ``pending`` and follows the message lifecycle again.
* **Remove**: discard it from the failure transport. Its row remains in
  the inspector as ``removed`` until cleanup, even if the envelope was
  already missing from the transport.

.. tip::

    Retrying runs the handler again, including side effects such as sending
    an email. Check the error and fix its cause before retrying.

Securing the Admin Actions
~~~~~~~~~~~~~~~~~~~~~~~~~~

Retry and remove both require ``ROLE_ADMIN`` by default. Users who can
open the inspector without that role can browse messages, but can't use
those actions. Protect read access through your controller or Symfony
Security configuration as shown above; action permissions don't restrict
who can view the page.

Configure the permission of each action separately. This grants retry
to your operators and keeps remove for administrators:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            actions:
                retry_permission: ROLE_MESSENGER_OPERATOR
                remove_permission: ROLE_ADMIN

Each value is checked with Symfony's ``isGranted()``, so you can use roles
(with role hierarchy) or attributes supported by your voters. Setting a
permission to ``null`` lets every user who can open the inspector perform
that action.

Deciding Message by Message with a Voter
........................................

For finer control, use a voter. It receives the stored message row as its
subject, including ``message_class``, ``status`` and ``transport_name``.
The subject is ``null`` for an unknown message, so handle that case too.

For example, this voter prevents retries on the billing transport::

    namespace App\Security\Voter;

    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Authorization\Voter\Voter;

    final class RetryMessageVoter extends Voter
    {
        protected function supports(string $attribute, mixed $subject): bool
        {
            return 'RETRY_MESSAGE' === $attribute;
        }

        protected function voteOnAttribute(
            string $attribute,
            mixed $subject,
            TokenInterface $token,
        ): bool {
            if (!\is_array($subject)) {
                return false;
            }

            return 'billing' !== $subject['transport_name'];
        }
    }

Then use the attribute of the voter as the configured permission:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            actions:
                retry_permission: RETRY_MESSAGE

Restricting the Actions from PHP
................................

You can also override ``denyUnlessActionAllowed()`` in your controller to
add a check before an action runs::

    use EasyCorp\Bundle\EasyAdminProBundle\MessengerInspector\Option\MessageAction;

    protected function denyUnlessActionAllowed(
        MessageAction $action,
        string $messageId,
    ): void {
        $this->denyAccessUnlessGranted('MANAGE_MESSAGE', $messageId);
    }

Here, ``MANAGE_MESSAGE`` is an attribute handled by your own voter. This
check runs **after** the configured permission check, so both must allow
the action. It doesn't control button visibility; use the configured
permissions for that.

View Options
~~~~~~~~~~~~

Use these methods on the ``MessengerInspector`` object returned by
``configureMessengerInspector()`` to customize the message list and detail
panel:

``setPageSize(int $pageSize)``
    Number of messages per page. Must be a positive integer.
    Defaults to ``30``.

``displayStackTrace(bool $display = true)``
    Show captured stack traces in the detail panel. Enabled by default;
    pass ``false`` to hide them. To capture traces, enable
    ``capture.error_trace`` (see `Storing Payloads`_).

``enableAutoRefresh(bool $enabled = true)``
    Start with auto-refresh turned on. Disabled by default. Users can
    still turn it on or off with the toggle above the list.

``setAutoRefreshInterval(int $seconds)``
    Number of seconds between automatic refreshes. Defaults to ``5``
    and must be at least ``1``. Setting the interval doesn't turn
    auto-refresh on.

``displayMessagePayload()``
    Show public message properties in the detail panel (the default).
    Private, protected and sensitive properties are hidden.

``displayFullMessagePayload()``
    Show all captured properties, including private and protected ones.
    Sensitive values remain redacted.

``hideMessagePayload()``
    Hide the payload from the detail panel. This doesn't stop capture or
    remove payloads already stored.

Payload display requires ``capture.payload`` to be enabled when the
message is recorded (see `Storing Payloads`_). Values of promoted
constructor parameters marked with ``#[\SensitiveParameter]`` are redacted
during capture, so even full display can't reveal them.

Recording Modes
---------------

Choose how much of the message lifecycle you want to see:

``minimal`` (default)
    Records dispatch and outcome: ``pending`` -> ``sent`` -> ``handled``,
    ``failed`` or ``retrying``. Start here for lower database write volume.

``full``
    Also records ``processing``, so you can see messages while a worker is
    handling them. This adds database writes.

To enable full tracking:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            mode: full

You can switch modes at any time. Existing rows keep their recorded status.
In full mode, the prune command can mark messages stuck in ``processing``
as ``stale`` (see `Pruning Old Records`_).

A ``pending`` message is being sent to its transports; ``sent`` means they
accepted it. On Symfony versions before 7.4, messages are marked ``sent``
immediately because Messenger doesn't provide send confirmation.

Messages routed to ``sync`` are handled during dispatch and go straight
to ``handled`` or ``failed``, without a ``processing`` step.

Excluding Messages
------------------

Not every message needs inspection. Use an attribute for a whole message
class, a stamp for one dispatch, or configuration for application-wide
rules:

By Attribute
~~~~~~~~~~~~

Add the ``DisableInspectionStamp`` PHP attribute to a message class to
exclude every instance of that class. This is the recommended approach
for messages that you control::

    namespace App\Message;

    use EasyCorp\Bundle\EasyAdminProBundle\MessengerInspector\Stamp\DisableInspectionStamp;

    #[DisableInspectionStamp]
    final class HeartbeatMessage
    {
    }

By Stamp
~~~~~~~~

The same class is also a Messenger stamp. Attach it to a single
envelope when you want to exclude one specific dispatch::

    use App\Message\ProcessReportMessage;
    use EasyCorp\Bundle\EasyAdminProBundle\MessengerInspector\Stamp\DisableInspectionStamp;
    use Symfony\Component\Messenger\Envelope;
    use Symfony\Component\Messenger\MessageBusInterface;

    public function dispatch(MessageBusInterface $bus): void
    {
        $bus->dispatch(new Envelope(new ProcessReportMessage(), [
            new DisableInspectionStamp(),
        ]));
    }

You can also pass ``onlyWhenNoHandler: true`` to keep the message inspected when
a handler exists, and skip it only when Messenger does not find any handler.
This is useful for messages that are dispatched optimistically.

By Configuration
~~~~~~~~~~~~~~~~

The ``included_messages`` and ``excluded_messages`` options take a list
of message FQCNs, interfaces or parent classes. ``included_messages``
defaults to ``'*'``, meaning every message is eligible unless it is
explicitly excluded. An exclusion always wins over an inclusion:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            included_messages:
                # Only inspect messages of these types or that
                # implement these interfaces.
                - App\Message\Contract\BillingMessage
            excluded_messages:
                - App\Message\InternalDebugMessage

By Sampling
~~~~~~~~~~~

For very high-volume applications, set a fractional sampling rate to
keep only a slice of the traffic. Sampling is deterministic per
message id, so a given message is either fully tracked across its
lifecycle or skipped entirely:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            sampling:
                # Keep 10% of messages.
                rate: 0.1

Tagging Messages
----------------

Give messages a description and tags to make them easier to find::

    use App\Message\OnboardEmployeeMessage;
    use EasyCorp\Bundle\EasyAdminProBundle\MessengerInspector\Stamp\DescriptionStamp;
    use EasyCorp\Bundle\EasyAdminProBundle\MessengerInspector\Stamp\TagStamp;
    use Symfony\Component\Messenger\MessageBusInterface;

    public function onboard(MessageBusInterface $bus, int $employeeId): void
    {
        $bus->dispatch(new OnboardEmployeeMessage($employeeId), [
            new DescriptionStamp(sprintf('Onboard employee #%d', $employeeId)),
            new TagStamp('onboarding'),
            new TagStamp('hr'),
        ]);
    }

Descriptions appear in the inspector and are searchable. You can add
several tags, but each must be non-empty and contain no commas. To filter
by tag, add ``?tag=onboarding`` to the inspector URL; there isn't a tag
control in the filter row. Comma-separated values select multiple tags.

Auto-Stamping
~~~~~~~~~~~~~

Mailer and Notifier messages get descriptions and tags automatically when
those optional components are installed:

* Mailer messages use the ``mailer`` tag and a description based on the
  subject and first recipient.
* Notifier messages use the ``notifier`` tag and the message subject.

Your own descriptions take precedence, and existing tags aren't duplicated.

Disable either integration if you don't need it:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            auto_stamp:
                mailer: true
                notifier: false

Storing Payloads
----------------

By default, the inspector stores metadata such as the message class,
status, description, tags and timings. Payloads and exception stack traces
are optional:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            capture:
                payload: true
                error_trace: true
                error_message_max_length: 4000

Before enabling payload capture, check whether your messages contain
passwords, tokens or other sensitive data. Hiding a payload in the
interface doesn't remove it from storage.

Capture and display are separate settings. Once captured, payloads and
traces appear according to your controller's `View Options`_. Promoted
constructor parameters marked with ``#[\SensitiveParameter]`` are redacted
during capture, including when full payload display is enabled.

Multiple Buses and Failure Transports
-------------------------------------

By default, the inspector monitors every Messenger bus
(``buses: '*'``). To restrict it to a subset, list the bus service
ids explicitly:

.. code-block:: yaml

    # config/packages/messenger.yaml
    framework:
        messenger:
            buses:
                messenger.bus.commands: ~
                messenger.bus.events: ~

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            buses:
                - messenger.bus.commands

Retry and remove automatically use the failure transport associated with
each receiver, so you can configure several failure transports.

These actions need a listable failure transport (one implementing
``ListableReceiverInterface``). If the transport can't be listed or the
envelope is no longer available, the inspector reports that it couldn't
find the message.

Commands
--------

Use the following commands to clean up inspector history and read
statistics from the terminal.

Pruning Old Records
~~~~~~~~~~~~~~~~~~~

Schedule ``easyadmin:messenger-inspector:prune`` to keep the history from
growing indefinitely, for example once a night with cron:

.. code-block:: terminal

    # Preview the cleanup.
    $ php bin/console easyadmin:messenger-inspector:prune --dry-run

    # Apply the configured retention periods.
    $ php bin/console easyadmin:messenger-inspector:prune

    # Prune only handled messages.
    $ php bin/console easyadmin:messenger-inspector:prune --status=handled

    # Use a different retention period for this run.
    $ php bin/console easyadmin:messenger-inspector:prune --older-than="30 days"

The default retention periods are 7 days for handled messages, 30 days
for failed messages (including ``removed``, ``retrying`` and ``stale``),
and 2 days for sent or pending messages. Set a period to ``0`` to disable
pruning for those statuses. See `Configuration Reference`_.

The command also marks messages left in ``processing`` longer than
``stale_processing_minutes`` as ``stale``, and cleans up the optional
events table. Use ``--skip-stale`` or ``--skip-events`` to skip those steps.

.. tip::

    Set ``stale_processing_minutes`` above your slowest handler's expected
    runtime. A long-running handler can look like a crashed worker while
    it's busy. If it finishes later, its message status updates normally.

To clear all inspector history, use ``--all``. It asks for confirmation;
add ``--force`` for a non-interactive run. You can preview it with
``--dry-run`` or keep event history with ``--skip-events``, but you can't
combine ``--all`` with ``--status`` or ``--older-than``. This deletes
inspector records, not messages from your Messenger transports.

Console Stats
~~~~~~~~~~~~~

The ``easyadmin:messenger-inspector:stats`` command prints aggregated
statistics from the inspector table to the console:

.. code-block:: terminal

    # Stats for the last 24 hours.
    $ php bin/console easyadmin:messenger-inspector:stats

    # Stats for an explicit time window.
    $ php bin/console easyadmin:messenger-inspector:stats \
        --from="2026-04-01T00:00:00Z" \
        --to="2026-04-30T23:59:59Z"

    # Limit to transports whose name contains "async" (case-insensitive)
    # and show the top 50 message classes and transports.
    $ php bin/console easyadmin:messenger-inspector:stats \
        --transport=async \
        --top=50

The output aggregates the data stored by the module: counts per
status, summary (success, failure, fail rate, average wait time,
average handle time), top message classes and top transports.

Configuration Reference
-----------------------

These are the options for message inspection and their defaults. Only
configure the values you want to change:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        messenger_inspector:
            enabled: false
            # null uses the global easyadmin_pro.dbal_connection value.
            dbal_connection: null
            # The events table adds an _events suffix to this name.
            table_name: easyadmin_messenger_inspector_messages
            # '*' for all buses, or a list of bus service ids.
            buses: '*'
            # minimal or full (includes the processing step).
            mode: minimal

            capture:
                payload: false
                error_trace: false
                # Maximum characters; 0 means no truncation.
                error_message_max_length: 2000

            # Days kept by the prune command; 0 disables pruning.
            retention_handled_days: 7
            retention_failed_days: 30
            retention_sent_days: 2
            # Set above your slowest handler's expected runtime.
            stale_processing_minutes: 15

            sampling:
                # 0.0 to 1.0; 1.0 records every eligible message.
                rate: 1.0
            # '*' or a list of message classes, interfaces or parent classes.
            included_messages: '*'
            excluded_messages: []

            auto_stamp:
                mailer: true
                notifier: true
            actions:
                # Roles or voter attributes; null removes the check.
                retry_permission: ROLE_ADMIN
                remove_permission: ROLE_ADMIN

            # Separate event history for your own queries.
            events:
                enabled: false
                retention_days: 7

            # Propagate storage errors instead of logging and continuing.
            fail_on_storage_error: false

Timings and Memory
------------------

The message details show three measurements:

* **Wait time**: time from dispatch until handling starts.
* **Handle time**: time spent in the bus middleware and handlers, excluding
  the inspector's storage work. It measures elapsed time, not CPU time.
* **Memory allocated**: the change in allocated memory during handling.
  This isn't peak memory or total worker memory. It can be negative if the
  handler frees more memory than it allocates.

Wait and handle times have millisecond precision, even though displayed
timestamps are stored with second precision. Failed attempts don't record
handle time or memory. Batch handlers don't record memory; their timings
are available only in full mode and start when the worker receives them.

Database Compatibility
----------------------

The inspector is tested with SQLite, MySQL 8, MariaDB 11 and PostgreSQL 16.
Oracle support is implemented but isn't covered by the test matrix. On
MySQL and MariaDB, use InnoDB and a ``utf8mb4`` collation.

.. _`Symfony Messenger`: https://symfony.com/doc/current/messenger.html
