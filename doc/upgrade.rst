Upgrading from EasyAdmin 2 to EasyAdmin 4
=========================================

In EasyAdmin 2 most of the backend configuration was defined in YAML files,
while custom behavior was created with PHP. This worked great for small
applications, but it was hard to maintain and not flexible enough for medium
and large applications.

Starting from EasyAdmin 3, **backends are created exclusively with PHP**.
YAML is no longer used in any part of EasyAdmin. However, you will be even more
productive than before because you can autocomplete 100% of the new PHP code and
the bundle also provides commands to generate some of the needed code.

There is a way to `upgrade from EasyAdmin 2 YAML files to EasyAdmin 3 PHP files automatically`_.
However, that upgrade feature was removed in EasyAdmin 4.x. That's why, if you
want to upgrade from EasyAdmin 2, it's recommended to upgrade first to EasyAdmin 3
and then, upgrade to EasyAdmin 4.x.

.. _`upgrade from EasyAdmin 2 YAML files to EasyAdmin 3 PHP files automatically`: https://symfony.com/bundles/EasyAdminBundle/3.x/upgrade.html
