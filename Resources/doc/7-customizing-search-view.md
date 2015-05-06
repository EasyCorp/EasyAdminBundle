Chapter 7. Customizing the Search View
======================================

In order to provide a consistent user experience, the `search` view reuses most
of the `list` view configuration. That's why the search results are displayed
using the same template and the same fields as in the listings.

Customize the Columns on which the Query is Performed
-----------------------------------------------------

By default, the search query is performed on all entity properties except those
with special data types, such as `binary`, `blob`, `object`, etc.

Define the `fields` option in the `search` configuration of any entity to
explicitly set the fields used to perform the query:

```yaml
# app/config/config.yml
easy_admin:
    entities:
        Customer:
            class: AppBundle\Entity\Customer
            search:
                fields: ['firstName', 'lastName', 'email']
    # ...
```
