# Task A: complete backend

Give this text to the agent verbatim.

---

Symfony project with EasyAdmin (easycorp/easyadmin-bundle 5.x) installed.
Entities in App\Entity: Product {id int, name string, slug string,
priceInCents int, currency string (ISO code), description ?string long text,
imageFilename ?string, status string ('draft'|'published'|'archived'),
isFeatured bool, category ManyToOne Category, tags ManyToMany Tag, shop
ManyToOne Shop, createdAt \DateTimeImmutable}; Category {id, name}; Tag {id,
name}; Shop {id, name, owner ManyToOne User}. Roles: ROLE_USER, ROLE_EDITOR,
ROLE_ADMIN. CategoryCrudController and TagCrudController already exist in
App\Controller\Admin. The Symfony routes 'admin_orders', 'app_settings' and
'app_profile' already exist.

Write:

1. src/Controller/Admin/ProductCrudController.php using explicit field
   classes: id only on index and detail; description only on detail and
   forms; createdAt not editable in forms; imageFilename as an image field
   (public base path /uploads/products, upload dir public/uploads/products,
   random file name keeping the extension); price as a money field stored as
   cents whose currency comes from the entity's currency property; category
   as an association with autocomplete; tags association; status as a choice
   field rendered as badges (draft=warning, published=success,
   archived=secondary); isFeatured boolean toggleable inline on index;
   default sort createdAt DESC; search by name and slug; 50 items per page; a
   custom 'publish' action on index rows and detail page, only for
   ROLE_EDITOR, that sets status=published, flushes, adds a flash and
   redirects to index; remove the delete action from index; filters for
   category, isFeatured, createdAt and status; scope the index query so users
   without ROLE_ADMIN only see products of shops they own; entity-level
   permission so only ROLE_EDITOR can access this CRUD.
2. src/Controller/Admin/DashboardController.php: title 'Acme Shop', favicon,
   locales en and es, a 'Catalog' menu section with Products, Categories,
   Tags; a 'Sales' section with an Orders item linking to the Symfony route
   'admin_orders'; an external 'Documentation' link to
   https://example.com/help opening in a new tab; a 'Settings' item (route
   'app_settings') restricted to ROLE_ADMIN; user menu showing avatar and
   name plus a 'My profile' item (route 'app_profile'); the dashboard index
   must redirect to the Product CRUD index.
3. tests/Controller/Admin/ProductCrudControllerTest.php: functional test
   using EasyAdmin's testing helpers that logs in and asserts the index page
   shows the 'Name' column and lists one product.

Return the complete PHP files.
