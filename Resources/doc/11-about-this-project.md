Chapter 11. About this project
==============================

The main author of this bundle works for SensioLabs, the company behind the
Symfony framework. However, this bundle is not promoted, endorsed or sponsored
by SensioLabs in any way. **This is not the official Symfony admin generator**.

Our philosophy
--------------

EasyAdmin is an open source project with a very **opinionated development**. We
don't make decisions by committee and we're not afraid to refuse the feature
requests proposed by our users.

We are a very young project and therefore, our resources and community are
still very limited. In order to survive as a project, we must focus on as few
features as possible and we must keep the original vision of the project.

These are some of our **development principles**:

  * Developers and backend users are our priorities. We'll always prioritize
    UX (user experience) and DX (developer experience) over code purity.
  * Backend customization is balanced between configuration options and code.
    We'll add new options when they are easy and make sense. Otherwise, we'll
    provide code extension points.
  * Features will only be added if they are useful for a majority of users and
    they don't overcomplicate the application code.
  * Documentation is more important than code. Everything must be documented
    and documentation must be always up-to-date.

Our roadmap
-----------

Our **short-term roadmap** of features that will be added soon is available in
[the list of project issues](https://github.com/javiereguiluz/EasyAdminBundle/issues).

**Long-term roadmap**

These are the features that we'll implement in the future when we find a
generic and simple way to do it:

  * Complete Doctrine association support (all kinds of associations: one-to-
    one, including self-referencing, one-to-many, many-to-one and many-to-many)
  * Allow to configure the main color used in the backend (to match the
    company's brand color)
  * Nested main menu items (two-level only)
  * Support for exporting the list or search results to CSV and/or Excel
  * Full theme support (not just changing the main color of the backend)
  * FOSUserBundle integration
  * Form field grouping
  * Custom actions for list/search, edit, show and new views.

**Features that we'll never implement**

From time to time we review the list of features requested by users. This means
that some of the following features may be included in EasyAdmin sometime in
the future. However, it's safe to consider that they'll never be implemented:

  * Support for Symfony-based applications built without the Symfony full-
    stack framework (Silex, Laravel, custom Symfony developments, etc.)
  * Support for anything different from Doctrine ORM (Propel, Doctrine ODM,
    etc.)
  * Support for fine-grained security control over views and/or entity actions
    (instead, use Symfony's built-in security features, such as voters or
    ACLs).
  * Dashboards for backend homepages (with widgets, charts, etc.)
  * Breadcrumbs that show the hierarchical navigation to the given page.
  * Batch actions to apply the same action to more than one list record
    simultaneously.
  * CMS-like features.
  * Assetic or frontend-tools-based (gulp, grunt, bower) asset processing.
  * Support for AngularJS or any other JavaScript-based client-side technology.

How to Collaborate in this Project
----------------------------------

**1.** Ideas, Feature Requests, Issues, Bug Reports and Comments (positive or
negative) are more than welcome.

**2.** Unsolicited Pull Requests are currently not accepted.

EasyAdmin is a very young project. In order to protect the original vision of
the project, we don't accept unsolicited Pull Requests. This decision will of
course be revised in the near term, once we fully realize how the project is
being used and what do users expect from us.

Alternative Projects
--------------------

EasyAdmin deliberately discards the most complex and customized backends,
focusing on the simplest 80% of the backend projects. In case you encounter an
unavoidable limitation to develop your backend with EasyAdmin, consider using
any of the following alternative admin generators:

  * [AdmingeneratorGeneratorBundle](https://github.com/symfony2admingenerator/AdmingeneratorGeneratorBundle),
    a project similar to EasyAdmin and based on YAML configuration files. It
    provides support for Propel, Doctrine ORM and Doctrine ODM models.
  * [SonataAdminBundle](https://github.com/sonata-project/SonataAdminBundle),
    the most advanced and most customizable admin generator for Symfony
    applications. There's nothing you can't do with Sonata.
  * [NgAdminGeneratorBundle](https://github.com/marmelab/NgAdminGeneratorBundle),
    an AngularJS-based admin generator compatible with any Symfony project
    that provides a RESTFul API.
