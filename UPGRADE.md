EasyAdmin Upgrade Guide
=======================

This document describes the backwards incompatible changes introduced by each
EasyAdminBundle version and the needed changes to be made before upgrading to
the next version.

Upgrade to 1.4.0
----------------

These changes affect you only if you have customized any of the following
templates in your backend:

1) `form/entity_form.html.twig` template has been renamed to `form.html.twig` 
2) `_list_paginator.html.twig` template has been renamed to `_paginator.html.twig`
3) `_flashes.html.twig` template has been removed because it wasn't used in any other template

Full version details: https://github.com/javiereguiluz/EasyAdminBundle/releases/tag/v1.4.0
