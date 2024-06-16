# Security Policy

## Supported Versions

| Branch  | Supported          |
| ------- | ------------------ |
| 1.x     | No                 |
| 2.x     | No                 |
| 3.x     | No                 |
| 4.x     | Yes                |

## Reporting a Vulnerability

1) This project relies on [Symfony components][1] for most of its features.
   If you suspect that the security issue is caused by Symfony, please report
   the issue to them as explained in [symfony.com/security][2]

2) Since this bundle is used to create admins with restricted access, most of
   the security issues don't have any practical impact and you can report them
   as regular issues.

3) If you find truly critical security issues (e.g. a way to bypass the
   restricted access to the admin) you can report them in this repository using
   the tool provided by GitHub in [Security > Advisories][3].

[1]: https://symfony.com/components
[2]: https://symfony.com/security
[3]: https://github.com/EasyCorp/EasyAdminBundle/security/advisories
