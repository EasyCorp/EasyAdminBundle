ROADMAP
=======

Short-Term Roadmap
------------------

Our **short-term roadmap** of features that will be added soon is available in
[the list of project issues](https://github.com/javiereguiluz/EasyAdminBundle/issues).

Long-Term Roadmap
-----------------

These are the features that we'll implement in the future when we find a
generic and simple way to do it:

  * [DONE] Complete Doctrine association support (all kinds of associations: one-to-
    one, including self-referencing, one-to-many, many-to-one and many-to-many)
  * [DONE] Allow to configure the main color used in the backend (to match the
    company's brand color)
  * [DONE] Nested main menu items (two-level only)
  * Support for exporting the list or search results to CSV and/or Excel
  * [DONE] Full theme support (not just changing the main color of the backend)
  * FOSUserBundle integration
  * Form field grouping in tabs
  * [DONE] Custom actions for list/search, edit, show and new views.
  * Support for fine-grained security control over views and/or entity actions
    (instead, use Symfony's built-in security features, such as voters or
    ACLs).
  * Batch actions to apply the same action to more than one list record
    simultaneously.
  * Embedding forms to create entities while editing or creating other entities.

Features That We'll Never Implement
-----------------------------------

Some of the following features may be included in EasyAdmin sometime in the
future if enough users ask for them and if we've completed all the basic features
first. However, it's safe to consider that they'll never be implemented:

  * Dashboards for backend homepages (with widgets, charts, etc.)
  * Support for Symfony-based applications built without the Symfony full-
    stack framework (Silex, Laravel, custom Symfony developments, etc.)
  * Support for anything different from Doctrine ORM (Propel, Doctrine ODM,
    etc.)
  * Breadcrumbs that show the hierarchical navigation to the given page.
  * CMS-like features.
  * Assetic or frontend-tools-based (gulp, grunt, bower) asset processing.
  * Support for AngularJS or any other JavaScript-based client-side technology.
