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

Since EasyAdmin doesn't define the format of the request, it's recommended to
define this format for all the backend URLs using the FOSRestBundle
configuration.

Open your main configuration file, look for the `fos_rest` configuration block
and add the following `format_listener` configuration (change the value of
the `path` option if your backend customized the URL prefix):

```yaml
# app/config/config.yml
fos_rest:
    format_listener:
        enabled: true
        rules:
            - { path: '^/admin', methods: ['GET', 'POST'], priorities: ['html'],
                fallback_format: 'html', prefer_extension: false }
```

[1]: https://github.com/FriendsOfSymfony/FOSRestBundle
