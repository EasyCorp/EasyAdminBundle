Audit Log
=========

.. note::

    This feature requires :doc:`EasyAdmin Pro </pro>`, a paid extension
    to EasyAdmin.

The audit log helps you answer a familiar question: who changed this,
and when? It records changes to your Doctrine entities, with the old and
new values, the person or process responsible, and the time of the change.
You can browse that history from your EasyAdmin backend.

You can also record events such as data exports or account impersonations.
Entries are signed by default, so you can check whether the stored history
has been tampered with.

Enabling the Audit Log
----------------------

The audit log is bundled with EasyAdmin Pro but disabled by default.
Enable it in your application configuration:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            enabled: true
            integrity:
                active_key_id: 'audit-2026-01'
                keys:
                    audit-2026-01: '%env(AUDIT_LOG_HMAC_KEY)%'

The audit log signs entries by default. Generate a dedicated secret of at
least 32 bytes and store it in the ``AUDIT_LOG_HMAC_KEY`` environment
variable or Symfony's secrets vault:

.. code-block:: terminal

    $ php -r "echo bin2hex(random_bytes(32));"

The ``keys`` map is your *key ring*. Each key has a name, such as
``audit-2026-01``, and ``active_key_id`` chooses the one used for new
entries. Keep old keys so you can still verify older entries (see
`Rotating Keys`_).

.. caution::

    Use a dedicated secret, not ``APP_SECRET``, and reference it with
    ``%env(...)%``. Don't put the secret itself in configuration files:
    anyone with the key and database write access can forge audit entries.

Next, generate a migration, review it, and apply it:

.. code-block:: terminal

    $ php bin/console make:migration
    $ php bin/console doctrine:migrations:migrate

This creates the audit table and its two integrity tables. See
:doc:`EasyAdmin Pro installation </pro>` to use a different database
connection or table name.
You can now edit an entity and see its changes in the audit log.

Displaying the Audit Log in Your Backend
----------------------------------------

The audit log ships with a ready-made web interface. To mount it, create
a controller that extends ``AbstractAuditLogController`` and expose it on
an admin route::

    // src/Controller/Admin/AuditLogController.php
    namespace App\Controller\Admin;

    use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
    use EasyCorp\Bundle\EasyAdminProBundle\AuditLog\Controller\AbstractAuditLogController;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[AdminRoute(path: '/audit-log', name: 'audit_log')]
    #[IsGranted('ROLE_ADMIN')]
    class AuditLogController extends AbstractAuditLogController
    {
    }

Add a link in your dashboard's ``configureMenuItems()`` method. For a
dashboard whose route name is ``admin``, the route above is named
``admin_audit_log``::

    use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

    yield MenuItem::linkToRoute(
        'Audit Log',
        'fa fa-clock-rotate-left',
        'admin_audit_log',
    );

.. note::

    The ``#[AdminRoute]`` attribute requires EasyAdmin pretty admin
    routes. If your dashboard does not use them, replace it with a plain
    ``#[Route]`` attribute and link to the route with
    ``MenuItem::linkToRoute()`` using the name you chose::

        use Symfony\Component\Routing\Attribute\Route;

        #[Route(path: '/admin/audit-log', name: 'admin_audit_log')]
        #[IsGranted('ROLE_ADMIN')]
        class AuditLogController extends AbstractAuditLogController
        {
        }

Open the page to browse changes, search, or filter by action, actor and
date. Click an entry to see its old and new values, actor, IP address and
metadata. Related entries are grouped together, and dates use your local
timezone.

.. tip::

    Click an actor's name to see their changes. The date filter also
    accepts a time of day, which helps when investigating a specific event.

View Options
~~~~~~~~~~~~

Override ``configureAuditLog()`` in your controller to customize the page::

    use EasyCorp\Bundle\EasyAdminProBundle\AuditLog\Config\AuditLog;
    use EasyCorp\Bundle\EasyAdminProBundle\AuditLog\Option\DateTimeFormat;
    use Symfony\Component\Security\Core\User\UserInterface;

    public function configureAuditLog(): AuditLog
    {
        return AuditLog::new()
            ->setPageSize(40)
            ->setUserName(
                fn (?UserInterface $user): string =>
                    $user?->getUserIdentifier() ?? '-',
            )
            ->setDateTimeFormat(DateTimeFormat::Medium, DateTimeFormat::Short);
    }

The available options are:

``setPageSize(int $pageSize)``
    Number of entry groups shown per page. Defaults to ``40`` in the
    controller that ships with the bundle (and to ``30`` when you build
    an ``AuditLog`` object without calling it). Must be a positive
    integer.

``setUserName(\Closure $callback)``
    Format the actor's name. The callback receives your user entity, or
    ``null`` if it can't be found, and returns the name to display.

``displayUserName(bool $display = true)``
    Whether to show the actor's name. Enabled by default.

``displayUserAvatar(bool $display = true)``
    Whether to show the actor's avatar. Disabled by default.

``setAvatarUrl(\Closure $callback)``
    Return an avatar URL for the user passed to the callback. Enable
    avatars with ``displayUserAvatar()`` to display it.

``setGravatarEmail(\Closure $callback)``
    Return the user's email address for a Gravatar avatar. This is used
    when no avatar URL callback is set.

``setDateTimeFormat(DateTimeFormat $dateFormat, DateTimeFormat $timeFormat)``
    Choose ``None``, ``Short``, ``Medium``, ``Long`` or ``Full`` for each
    part of the timestamp. Both default to ``Short``.

What Gets Recorded
------------------

Tracked Actions
~~~~~~~~~~~~~~~

For every tracked entity, the Doctrine listener records these actions:

* **Created** (``entity.created``): a new entity was persisted.
* **Updated** (``entity.updated``): a tracked property changed.
* **Deleted** (``entity.deleted``): the entity was removed.
* **Restored** (``entity.restored``): a previously soft-deleted entity
  was brought back.

Changes to owning-side associations (``ManyToOne`` and the owning side
of ``OneToOne``) are tracked as well, because Doctrine includes them in
the entity change set. Changes to collection-valued associations
(``OneToMany`` and ``ManyToMany``) are not tracked. The full snapshot
stored for create and delete entries includes every to-one association,
owning or inverse side.

Soft deletes are tracked by default (``track_soft_deletes``). The bundle
recognizes a soft delete by the conventional ``deletedAt`` field: when
that field goes from ``null`` to a date, the change is recorded as a
delete instead of an update, and the reverse is recorded as a restore.
If an entity uses a different field name, map it with
``soft_delete_field``:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            soft_delete_field:
                'App\Entity\Order': archivedAt

Choosing Which Entities Are Tracked
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default the audit log tracks every Doctrine entity in your
application (``included_entities: '*'``). To limit tracking to a
specific set of entities, replace ``'*'`` with a list of entity FQCNs:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            included_entities:
                - 'App\Entity\Order'
                - 'App\Entity\Invoice'

Use ``excluded_entities`` to keep tracking everything except a few
classes:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            excluded_entities:
                - 'App\Entity\WebhookLog'

Excluding Sensitive Properties
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Some properties must never appear in the audit log. The
``excluded_properties.global`` option lists property names that are
stripped from the diff of every entity. It defaults to a set of common
sensitive property names:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            excluded_properties:
                global:
                    - password
                    - plainPassword
                    - salt
                    - token
                    - apiKey
                    - secret
                    - twoFactorCode
                    - resetToken
                    - confirmationToken
                entities:
                    'App\Entity\User':
                        - lastLoginIp
                    'App\Entity\Payment':
                        - cardNumber
                        - cvv

The ``entities`` map adds per-entity exclusions on top of the global
list. Excluded properties are removed from the recorded diff, but the
audit entry itself is still created when other properties change.

This is different from ``skip_if_only_changed``, which discards the
entire audit entry (no row is written at all) when the *only* properties
that changed are in the list. It applies to updates only; create,
delete and restore entries are always recorded. Use it for noisy
bookkeeping fields such as ``updatedAt`` or ``lastLoginAt`` that would
otherwise create an entry on every request:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            skip_if_only_changed:
                global:
                    - updatedAt
                entities:
                    'App\Entity\User':
                        - lastLoginAt
                        - currentSessionToken

If one of those properties changes alongside a tracked property, the
entry is still created and includes the changes normally.

Tracking Scope
~~~~~~~~~~~~~~

The ``tracking_scope`` option controls in which context Doctrine entity
changes are recorded:

``all`` (default)
    Record changes everywhere: inside EasyAdmin, in your own
    controllers, in commands, in Messenger handlers, and so on.

``only_easyadmin``
    Record changes only when they happen inside an EasyAdmin backend
    request. Changes made elsewhere are ignored.

``except_easyadmin``
    Record changes everywhere except inside EasyAdmin backend requests.

The client IP address is stored with each entry by default. Set
``store_ip_address`` to ``false`` to omit it (for example, to reduce the
amount of personal data you retain).

Write Failure Policy
~~~~~~~~~~~~~~~~~~~~

By default, an audit write failure is logged and your application keeps
the entity change (``on_write_failure: fail_open``). Choose
``fail_closed`` if a change must not succeed without an audit entry:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            on_write_failure: fail_closed

For this guarantee, the audit log must use the **same database connection**
as the entity. The change and its audit entry then commit or roll back
together. With separate connections, ``fail_closed`` reports the error,
but the entity change has already been committed and cannot be rolled back.

Migrations pause logging automatically (see `Migrations`_).

Recording Custom Events
-----------------------

Not every event worth auditing is a Doctrine entity change. To record an
arbitrary event, inject ``AuditLoggerInterface`` and call ``log()``::

    namespace App\Controller;

    use App\Entity\User;
    use EasyCorp\Bundle\EasyAdminProBundle\Contracts\AuditLog\AuditLoggerInterface;

    final class DataExportController
    {
        public function __construct(
            private readonly AuditLoggerInterface $auditLogger,
        ) {
        }

        public function export(User $user): void
        {
            // ... generate the export

            $this->auditLogger->log(
                action: 'gdpr.data_export',
                description: 'Exported personal data on user request',
                subject: $user,
                metadata: [
                    'format' => 'json',
                    'requested_by' => 'data_subject',
                ],
            );
        }
    }

Choose an action name such as ``gdpr.data_export`` so related events are
straightforward to filter. The ``subject`` identifies the object involved,
and ``metadata`` holds any extra context you want to keep. For built-in
entity actions, you can also pass a value from the
``EasyCorp\Bundle\EasyAdminProBundle\AuditLog\Option\Action`` enum.

The actor is filled in automatically: the logged-in user, the running
console command, or the system. To record an event on someone else's
behalf, pass ``actor: $user``; for a background process, you can use a
string such as ``actor: 'cron:nightly'``.

Suspending Audit Logging
------------------------

To skip audit entries during an import or fixture load, inject
``AuditLoggerInterface`` and wrap the work in ``withoutLogging()``::

    // src/Service/UserImport.php
    namespace App\Service;

    use Doctrine\ORM\EntityManagerInterface;
    use EasyCorp\Bundle\EasyAdminProBundle\Contracts\AuditLog\AuditLoggerInterface;

    final class UserImport
    {
        public function __construct(
            private readonly AuditLoggerInterface $auditLogger,
            private readonly EntityManagerInterface $entityManager,
        ) {
        }

        public function import(iterable $users): void
        {
            $this->auditLogger->withoutLogging(function () use ($users) {
                foreach ($users as $user) {
                    $this->entityManager->persist($user);
                }

                $this->entityManager->flush();
            });
        }
    }

Both entity changes and explicit ``log()`` calls are skipped inside the
callback. Logging returns to its previous state afterward, even if an
exception is thrown. Keep the ``flush()`` inside the callback.

This only affects the current process. If the callback dispatches a
message to an asynchronous worker, wrap the work in that handler too.

Manual Disable / Enable
~~~~~~~~~~~~~~~~~~~~~~~

When the callback shape does not fit the workflow (streaming work with
intermediate flushes and recoverable errors), call ``disable()`` and
``enable()`` explicitly::

    $this->auditLogger->disable();
    try {
        foreach ($this->stream->records() as $batch) {
            $this->em->persist($batch);
            $this->em->flush();
            $this->em->clear();
        }
    } finally {
        $this->auditLogger->enable();
    }

Call ``isEnabled()`` when you need to know whether logging is currently
active, for example to skip work that only matters when entries are
being recorded.

Migrations
~~~~~~~~~~

Audit logging pauses automatically while Doctrine migrations run, provided
``doctrine/migrations`` is installed. You don't need to disable it yourself.
Direct SQL changes aren't tracked by the Doctrine ORM listener in any case.

If you want to record that a migration completed, call ``log()`` from a
command or service after the migration.

Disabling the Audit Log in the Test Environment
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you don't need audit entries in your tests, disable the feature for
the ``test`` environment:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    when@test:
        easyadmin_pro:
            audit_log:
                enabled: false

Keep it enabled in development to try out your audit setup. You can use
``withoutLogging()`` when loading fixtures.

Tamper-Evident Integrity
------------------------

Integrity checks help you detect changes to the audit history itself.
Each entry is signed with an HMAC and linked to the previous entry. A
*checkpoint* is a signed summary of a segment of that chain.

Your day-to-day tasks are to create checkpoints, keep copies outside the
database, and run verification. The sections below explain each step.

Checkpoints
~~~~~~~~~~~

Run this command regularly, for example once a minute with cron or
`Symfony Scheduler`_:

.. code-block:: terminal

    $ php bin/console easyadmin:audit-log:checkpoint

It creates a checkpoint when either configured threshold is reached:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            integrity:
                checkpoints:
                    every_entries: 1000
                    max_age_seconds: 300

These thresholds are checked **when the command runs**; they don't start a
background task. To cover pending entries immediately, bypass the
thresholds with ``--force``:

.. code-block:: terminal

    $ php bin/console easyadmin:audit-log:checkpoint --force

If all entries are already covered, no new checkpoint is created.

Anchoring Checkpoints Off-Site
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Keep checkpoint copies outside the database, in storage your application
cannot overwrite or delete. These copies give you an independent record
to compare against if the database is changed or restored from a backup.

Use JSON output to export the signed checkpoint:

.. code-block:: terminal

    $ php bin/console easyadmin:audit-log:checkpoint --format=json

Connect this output to your archival process, for example using storage
with a write-once retention policy. When ``created`` is ``true``, archive
the returned checkpoint, including its payload and signature. The bundle
doesn't upload checkpoints or compare them with external copies for you.

Verifying the Chain
~~~~~~~~~~~~~~~~~~~

Check the stored entries and checkpoints with:

.. code-block:: terminal

    $ php bin/console easyadmin:audit-log:verify

The command only reads data. Schedule it regularly and alert on a non-zero
exit code:

=================  =========  ==============================================
Result             Exit code  What to do
=================  =========  ==============================================
``VALID (local)``  ``0``      The local history is consistent. Compare with
                              your external checkpoint copies for
                              independent assurance.
``INVALID``        ``1``      A hash, signature or chain link doesn't match.
                              Investigate the affected history.
``UNVERIFIABLE``   ``2``      Verification couldn't finish. The report
                              identifies missing keys, checkpoints or chain
                              data. Restore what's missing and run it again.
=================  =========  ==============================================

For scripts, use ``--format=json`` and check ``exit_code``. Other useful
options are ``--chain=<id>`` to verify an older chain and ``-v`` to print
full hashes in the text report. Disabled integrity and unexpected errors
also produce exit code ``2``.

.. tip::

    Watch ``unanchored_tail_count`` in the JSON report. If it keeps growing,
    check that your checkpoint command is still running.

Entries recorded before integrity was enabled are counted separately;
they have no signatures and aren't included in chain verification.

Understanding the Guarantees
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Verification detects altered entries and broken links, including gaps
caused by deleted or reordered entries. It also checks the end of the
chain against the stored chain head and signed checkpoints.

There are three limits to keep in mind:

* A valid chain doesn't prove that every application change was logged.
  For that, use ``fail_closed`` on the same database connection as your
  entities (see `Write Failure Policy`_).
* Someone with database write access can remove entries after the latest
  checkpoint and rewind the chain head without local verification
  detecting it. Frequent checkpoints keep this window small.
* Someone with both database write access and a signing key can rewrite
  entries and local checkpoints. Checkpoints stored independently outside
  the database let you detect changes to the history they cover.

Integrity makes changes detectable; it doesn't prevent database edits.
A successful local check also can't detect a whole database restored to
an older, internally consistent backup. See `Restoring a Database Backup`_.

Rotating Keys
~~~~~~~~~~~~~

To switch signing keys, add the new secret to your key ring and select it
as the active key:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            integrity:
                active_key_id: 'audit-2026-07'
                keys:
                    audit-2026-01: '%env(AUDIT_LOG_HMAC_KEY)%'
                    audit-2026-07: '%env(AUDIT_LOG_HMAC_KEY_NEXT)%'

Deploy the configuration to every application process, including workers,
then create and export a checkpoint. New entries use the new key; existing
entries keep their original signatures. For a rolling deployment, distribute
the new key to all processes before changing ``active_key_id``.

Keep old keys for as long as any entry **or checkpoint** needs them. The
verification report lists these under ``key_ids_seen``. Checkpoints are
never deleted by cleanup, so a key that signed one must be retained
indefinitely.

Use stable key names of up to 64 characters, starting with a letter or
digit and containing only letters, digits, ``.``, ``_`` or ``-``. The
``algorithm`` option supports ``sha256`` (default), ``sha384`` and
``sha512``. Changing it affects new signatures; old ones remain verifiable.

Cleaning Up Old Entries
-----------------------

Use ``easyadmin:audit-log:cleanup`` to remove old entries. It previews the
changes by default; add ``--force`` to delete them:

.. code-block:: terminal

    # Preview cleanup using retention_days (365 days by default).
    $ php bin/console easyadmin:audit-log:cleanup

    # Apply the configured retention policy.
    $ php bin/console easyadmin:audit-log:cleanup --force

    # Use a different window for this run.
    $ php bin/console easyadmin:audit-log:cleanup --older-than="6 months" --force
    $ php bin/console easyadmin:audit-log:cleanup --days-to-keep=90 --force

Schedule the command with ``--force`` to apply your retention policy
regularly, for example nightly with cron or `Symfony Scheduler`_. Setting
``retention_days`` alone doesn't delete anything. A value of ``0`` keeps
entries indefinitely unless you pass an explicit cleanup option.

Use only one selection option at a time: ``--older-than`` (a date or
period), ``--days-to-keep`` (a positive number of days), ``--all`` (every
entry), or ``--reset-chain`` (see `Starting a New Chain`_).

How Retention Interacts With Integrity
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When integrity is enabled, entries are not deleted individually: the log
is a hash chain, and removing an arbitrary entry would break it. Cleanup
therefore deletes only whole segments that a checkpoint has already
closed, always starting from the oldest, so the surviving chain stays
verifiable. The checkpoint that closed the last deleted segment becomes
the trusted starting point of what remains.

This has two consequences:

* **The open tail is never deleted.** Entries newer than the last
  checkpoint are always kept, whatever their age. If a cleanup reports
  that everything was retained, create a checkpoint first and run it
  again:

  .. code-block:: terminal

      $ php bin/console easyadmin:audit-log:checkpoint --force
      $ php bin/console easyadmin:audit-log:cleanup --older-than="6 months" --force

* **Retention granularity equals the checkpoint cadence.** When the
  cutoff date falls inside a checkpointed segment, that whole segment is
  kept. Deletion never leaves a partial segment behind.

The checkpoints and the chain head are never deleted, so the log stays
verifiable after cleanup. When integrity is disabled, the command deletes
by date without the checkpoint restriction.

``--all`` forces a final checkpoint over the full history before deleting
every entry, then keeps the chain head and all checkpoints: the next
entry continues at the next sequence, chained to the head's last hash.

Starting a New Chain
~~~~~~~~~~~~~~~~~~~~

Use ``--reset-chain`` to close the current chain with a final checkpoint
and begin a brand-new one. It deletes nothing, but it requires both the
audit log and its integrity feature to be enabled (otherwise the command
fails with exit code ``1``):

.. code-block:: terminal

    # preview the reset (dry-run)
    $ php bin/console easyadmin:audit-log:cleanup --reset-chain

    # actually reset the chain
    $ php bin/console easyadmin:audit-log:cleanup --reset-chain --force

The old chain stays in the database, still verifiable with
``easyadmin:audit-log:verify --chain=<old id>``, while new entries begin a
fresh chain at sequence 1. The stream's checkpoint history links across
the reset, so it stays continuous. This is the explicit, recorded way to
start over. See `Restoring a Database Backup`_ for when you need it.

Restoring a Database Backup
---------------------------

A restore rolls entries, checkpoints and the chain head back together, so
local verification may still pass. Newer checkpoint copies stored outside
the database reveal the missing history.

Record which backup you restored and why, and keep that incident record
with your external checkpoints. If you need a clear boundary for new
activity, start a new chain:

.. code-block:: terminal

    $ php bin/console easyadmin:audit-log:cleanup --reset-chain --force

As new entries arrive, resume checkpoint exports for the restored system.
A forced checkpoint creates nothing when all surviving entries are already
covered. Keep the earlier external copies: they document the history lost
in the restore.

Processing Entries Before They Are Stored
-----------------------------------------

You can transform audit data before it is written, for example to mask
sensitive values or attach extra metadata. Implement
``AuditLogProcessorInterface`` (four methods) or, more conveniently,
extend ``AbstractAuditLogProcessor`` and override only the methods you
need. The abstract class provides pass-through implementations of all of
them:

``processProperty(string $entityFqcn, string $propertyName, mixed $value): mixed``
    Transform a single property value. Return the (possibly modified)
    value.

``processAuditData(string $entityFqcn, array $data): array``
    Transform the complete audit data array (which holds ``old_data``
    and/or ``new_data``). Return the modified array.

``addMetadata(string $entityFqcn, array $metadata): array``
    Add or change metadata stored with the entry. Return the modified
    metadata array.

``supports(string $entityFqcn): bool``
    Return ``true`` if this processor should handle the given entity.
    The default implementation returns ``true`` for every entity.

Annotate the processor with the ``#[AsAuditLogProcessor]`` attribute to
declare its priority and, optionally, which entities it applies to. The
``included`` and ``excluded`` parameters are mutually exclusive:
``included`` restricts the processor to the listed entities, while
``excluded`` runs it for every entity but the listed ones. Processors run
in descending priority order (higher priority first)::

    // src/AuditLog/IbanMaskingProcessor.php
    namespace App\AuditLog;

    use App\Entity\BankAccount;
    use EasyCorp\Bundle\EasyAdminProBundle\AuditLog\Attribute\AsAuditLogProcessor;
    use EasyCorp\Bundle\EasyAdminProBundle\AuditLog\Processor\AbstractAuditLogProcessor;

    #[AsAuditLogProcessor(priority: 0, included: [BankAccount::class])]
    final class IbanMaskingProcessor extends AbstractAuditLogProcessor
    {
        public function processProperty(
            string $entityFqcn,
            string $propertyName,
            mixed $value,
        ): mixed {
            if ('iban' !== $propertyName || !\is_string($value) || '' === $value) {
                return $value;
            }

            // keep only the last 4 characters, mask the rest
            return str_repeat('*', max(0, \strlen($value) - 4)).substr($value, -4);
        }
    }

In a standard Symfony application that's all you need: services are
autoconfigured by default, so any service implementing
``AuditLogProcessorInterface`` (or carrying the ``#[AsAuditLogProcessor]``
attribute) is registered as a processor automatically. If your processor
is not autoconfigured, tag it with ``easyadmin.audit_log.processor``
yourself:

.. code-block:: yaml

    # config/services.yaml
    services:
        App\AuditLog\IbanMaskingProcessor:
            autoconfigure: false
            tags: ['easyadmin.audit_log.processor']

The processor's ``supports()`` method is checked in addition to the
attribute filter, so both must allow an entity for the processor to run.

Performance
-----------

For busy applications, track the entities and properties you actually
need. Large create/delete snapshots cost more to store than small updates,
and ``skip_if_only_changed`` helps avoid entries for bookkeeping changes.

Integrity checks serialize writes through a shared chain head. Measure
throughput with your own workload, especially for bulk imports. Using
``fail_closed`` on the entity's connection also adds work per entry to the
transaction. Use ``withoutLogging()`` for imports you don't need to audit.

Configuration Reference
-----------------------

These are the available options and their defaults. You only need to
configure the values you want to change:

.. code-block:: yaml

    # config/packages/easyadmin_pro.yaml
    easyadmin_pro:
        audit_log:
            enabled: false
            # null uses the global easyadmin_pro.dbal_connection value.
            dbal_connection: null
            table_name: easyadmin_audit_log

            integrity:
                enabled: true
                # sha256, sha384 or sha512.
                algorithm: sha256
                # Required when integrity is enabled. See the setup above.
                active_key_id: null
                keys: []
                # Thresholds checked by the checkpoint command.
                checkpoints:
                    enabled: true
                    every_entries: 1000
                    max_age_seconds: 300
                # null uses <table_name>_chain_head and <table_name>_checkpoint.
                head_table_name: null
                checkpoint_table_name: null

            # Applied by the cleanup command; 0 keeps entries indefinitely.
            retention_days: 365
            # '*' for all entities, or a list of entity class names.
            included_entities: '*'
            excluded_entities: []

            excluded_properties:
                global:
                    - password
                    - plainPassword
                    - salt
                    - token
                    - apiKey
                    - secret
                    - twoFactorCode
                    - resetToken
                    - confirmationToken
                # Map entity class names to lists of property names.
                entities: []

            # Skip updates where only these properties changed.
            skip_if_only_changed:
                global: []
                entities: []

            track_soft_deletes: true
            # Map entity class names to field names (default: deletedAt).
            soft_delete_field: []
            store_ip_address: true
            # all, only_easyadmin or except_easyadmin.
            tracking_scope: all
            # fail_open or fail_closed; see Write Failure Policy.
            on_write_failure: fail_open

.. _`Symfony Scheduler`: https://symfony.com/doc/current/scheduler.html
