How To Integrate FOSRestBundle and EasyAdmin
============================================

[FOSRestBundle][1] provides various tools to rapidly develop RESTful APIs in
Symfony applications. EasyAdmin doesn't integrate with FOSRestBundle features in
any way, but there are some options that you may need to configure to avoid
errors in backend URLs.

Format Listener
---------------

This listener provided by FOSRestBundle determines the best format for the
request based on the HTTP Accept header included in the request and some format
priority configuration.

If you have enabled this format listener, disable it for the backend routes:

```
# app/config/config.yml
fos_rest:
    format_listener:
        enabled: true
        rules:
            # ... previous rules declarations
            - { path: '^/admin', stop: true }  # <-- add this line
```

When using FOSRestBundle 2.0, you may also need to configure the "zones" as
explained in [this chapter][2] of the FOSRestBundle documentation.

[1]: https://github.com/FriendsOfSymfony/FOSRestBundle
[2]: http://symfony.com/doc/master/bundles/FOSRestBundle/3-listener-support.html
