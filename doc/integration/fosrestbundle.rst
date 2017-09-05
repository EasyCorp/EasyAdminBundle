Integrating FOSRestBundle to Create APIs
========================================

`FOSRestBundle`_ provides various tools to rapidly develop RESTful APIs in
Symfony applications. EasyAdmin doesn't integrate with FOSRestBundle features in
any way, but there are some options that you may need to configure to avoid
errors in backend URLs.

Format Listener
---------------

This listener provided by FOSRestBundle determines the best format for the
request based on the HTTP Accept header included in the request and some format
priority configuration.

If you have enabled this format listener, disable it for the backend routes:

.. code-block:: yaml

    # app/config/config.yml
    fos_rest:
        format_listener:
            enabled: true
            rules:
                # ... previous rules declarations
                - { path: '^/admin', stop: true }  # <-- add this line

When using FOSRestBundle 2.0, you may also need to configure the "zones" as
explained in `this chapter`_ of the FOSRestBundle documentation.

.. _`FOSRestBundle`: https://github.com/FriendsOfSymfony/FOSRestBundle
.. _`this chapter`: https://symfony.com/doc/master/bundles/FOSRestBundle/3-listener-support.html
