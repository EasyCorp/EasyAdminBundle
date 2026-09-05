# Task B: actions, permissions and CSRF on an existing CRUD

This task targets the rules that describe behavior rather than method names:
CSRF handling of custom actions, what `setEntityPermission()` blocks, how
menu links to routes fail, and the defaults of the URL generator. Give this
text to the agent verbatim.

---

Symfony project with EasyAdmin (easycorp/easyadmin-bundle 5.x) installed.
App\Controller\Admin\DashboardController exists with the dashboard route
named 'admin'. App\Controller\Admin\ProductCrudController exists and manages
App\Entity\Product {id int, name string, status string ('draft'|'published'),
shop ManyToOne Shop}; Shop {id, name, owner ManyToOne User}. Roles:
ROLE_USER, ROLE_EDITOR, ROLE_ADMIN. No other Symfony routes exist besides the
ones EasyAdmin generates.

Implement, in the existing files:

1. A 'publish' action on the index rows and on the detail page of the Product
   CRUD that sets status='published', flushes, adds a flash message and
   redirects to the index page. It must be executed with the HTTP POST method
   only, it must be protected against CSRF, and only users with ROLE_EDITOR
   may see it or run it.
2. A 'Mark as draft' batch action on the index page, also POST-only and
   CSRF-protected, that sets status='draft' on the selected products.
3. Users without ROLE_ADMIN must only see, open and edit products whose shop
   they own. Users who try to open the detail page of another shop's product
   must be denied. Explain in a comment what EasyAdmin does on the index and
   on the detail page for the denied products.
4. In the dashboard menu, add a 'Reports' item that opens a new page at
   /admin/reports rendered by a method of the dashboard controller, and a
   'Products' item that opens the Product CRUD index.
5. A functional test with EasyAdmin's testing helpers that logs in a
   ROLE_EDITOR user who owns one shop, asserts the index page shows only that
   shop's products and that the 'publish' action is present for one of them,
   and asserts that opening the detail page of a product from another shop is
   denied.

Return the complete modified PHP files.
