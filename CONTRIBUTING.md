Contribution Guidelines
=======================

First of all, each single contribution is appreciated, whether a typo fix,
improved documentation, a fixed bug or a whole new feature.

## Feature request

If you think a feature is missing, please report it or implement it . If you report it, describe the more
precisely what you would like to see implemented. It would be nice if you can do
some search before submitting it and link the resources to your description.

## Bug report

If you think you have detected a bug or a doc issue, please report it or even better fix it. If you report it,
please be the more precise possible. Here a little list of required informations:

 * Symfony-standard fork which reproduces the bug.
 * Precise description of the bug.
 * Symfony version used.
 * Bundle version used.

## Making your changes

 1. Fork the repository on GitHub
 2. Pull requests must be sent from a new hotfix/feature branch, not from `master`.
 3. Make your modifications, coding standard for the project is [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
 4. Commit small logical changes, each with a descriptive commit message.
    Please don't mix unrelated changes in a single commit.

## Commit messages

Please format your commit messages as follows:

    Short summary of the change (up to 50 characters)

    Optionally add a more extensive description of your change after a
    blank line. Wrap the lines in this and the following paragraphs after
    72 characters.

## Submitting your changes

 1. Push your changes to a topic branch in your fork of the repository.
 2. [Submit a pull request][pr] to the original repository.
    Describe your changes as short as possible, but as detailed as needed for
    others to get an overview of your modifications.
 3. If you have reworked you patch, please squash all your commits in a single one with the following commands (here, we
    will assume you would like to squash 3 commits in a single one):

    ``` bash
    $ git rebase -i HEAD~3
    ```
 4. If your branch conflicts with the master branch, you will need to rebase and repush it with the following commands:

    ``` bash
    $ git remote add upstream git@github.com:javiereguiluz/EasyAdminBundle.git
    $ git pull --rebase upstream master
    $ git push origin bug-fix-description -f
    ```
## Further information

 * [General GitHub documentation][gh-help]
 * [GitHub pull request documentation][gh-pr]

 [gh-help]: https://help.github.com
 [gh-pr]:   https://help.github.com/send-pull-requests
 [issue]:   https://github.com/javiereguiluz/EasyAdminBundle/issues/new
 [pr]:      https://github.com/javiereguiluz/EasyAdminBundle/pull/new
