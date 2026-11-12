# Trigger queries

Agents decide whether to load the skill from its `description` alone. These
queries check that decision. Give each one verbatim, in a new session, to an
agent working in the evaluation application.

## The skill must load

1. Add a "Reviews" section to the admin so that editors can approve or reject
   product reviews.
2. Create a CRUD controller for the Category entity.
3. In ProductCrudController, show the price as money and hide the slug on the
   index page.
4. Add a "Publish" button to each row of the products list in the backend.
5. Only users with ROLE_ADMIN should be able to delete orders from the admin
   panel.
6. Let admins filter the list of users by registration date and by role.
7. Add a link to the sales report in the sidebar menu of the dashboard.
8. Write a functional test for the admin page that lists the products.
9. The edit form of the admin for products is too long. Split it in tabs.
10. src/Controller/Admin/DashboardController.php throws "must apply the
    #[AdminDashboard] attribute". Fix it.

## The skill must not load

1. Create a controller with a /products route that returns the products as
   JSON.
2. Add a ProductType form with name and price fields and render it in the
   public checkout page.
3. Add a Sonata Admin class for the Product entity.
4. Expose the Product entity as an API Platform resource with a search filter.
5. Add a `createdAt` property to the Product entity and generate the
   migration.
6. Protect every URL that starts with /account so that it requires
   ROLE_USER.
7. Write a console command that deletes the products archived more than one
   year ago.
8. Add a Stimulus controller that toggles the main menu of the public
   website on mobile.
9. Configure a Messenger transport to send the order confirmation emails
   asynchronously.
10. Write a functional test for the public product page at /products/{slug}.
