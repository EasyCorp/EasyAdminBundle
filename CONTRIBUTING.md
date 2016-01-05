Contribution Guidelines
=======================

Thank you for considering contributing to this bundle. We welcome any kind of
contribution, no matter if its huge or small, about documentation or code. We
also welcome any kind of developers, from experts to people who just started
working on Open-Source projects.

Requirements
------------

Before your first contribution, make sure you'll meet these requirements:

 * You have a user account on [GitHub](https://github.com/).
 * You have installed in your computer a working environment to develop PHP
   applications.
 * You have a basic level of English (code, docs and discussions are in English).

All submitted contributions (both code and documentation) adhere implicitly to
the [Open-Source MIT License][mit-license].

Proposing New Features
----------------------

We are determined to maintain the original simple and pragmatic philosophy of
the bundle. This means that we routinely reject any feature that complicates the
code too much or which doesn't fit in the bundle's philosophy.

That's why **we strongly recommend you** to propose new features by
[opening a new issue][create-issue] in the repository to discuss about them
instead of submitting a pull request with the code of the proposed feature.

Reporting Bugs
--------------

 1. Go to [the list of EasyAdmin issues][easyadmin-issues] and look for any
    existing bug similar to yours.
 2. If the bug hasn't been reported yet, [create a new issue][create-issue] and
    provide the following information:
    * Short but precise description of the bug (attach screenshots if needed);
    * Symfony version used (because we support both 2.x and 3.x versions);
    * EasyAdmin version (if it's not the latest one).

If we cannot reproduce the bug with the information provided, we may ask you to
create a fork of the [EasyAdmin Demo Application][easyadmin-demo] or the
[Symfony Standard Edition][symfony-standard] reproducing the bug.

Sending Pull Requests
---------------------

### Making your changes

 1. Fork [the EasyAdmin repository][easyadmin-repository] on GitHub and clone it
    in your computer:

    ```bash
    $ git clone git://github.com/<YOUR GITHUB USERNAME>/EasyAdminBundle.git
    ```

 2. Create a new branch for the new code (if you are fixing a bug, you can call
    this branch `fix_NNN`, where `NNN` is the number of the related issue):

    ```bash
    $ git checkout -b fix_NNN
    ```

 3. Make your code changes (use the same code syntax as Symfony described in
    [PSR-2][psr2-standard]) and submit the changes. Make sure that your code is
    **compatible with PHP 5.3 and Symfony 2.3**. Sometimes it's tricky to develop
    code compatible with Symfony 2.3 and 3.x. If you get stuck, just ask us and
    we'll help you.

### Submitting your changes

 1. Commit and push your changes to your own fork:

    ```bash
    # optional: needed only if you have added new files
    $ git add --all

    $ git commit path/to/modified/files
    # alternative: "git commit -a" to commit all the modified files

    # if this doesn't work, try: "git push origin <branch_name>"
    $ git push
    ```

 2. Go to the GitHub website and [create a new pull request][create-pr] in the
    EasyAdmin repository.
 3. Provide a short description about the changes made by the pull request.
    If you are fixing a bug, add the text `Fixed #NNN` and provide the number of
    the related issue (this allows us to track which bugs have already been fixed).
 4. There is no need to "squash" your commits. We'll do that for you.

In case some changes are merged in the repository since you submitted your pull
request, we may ask you to rebase it to make it mergeable again:

```bash
$ git remote add upstream git@github.com:javiereguiluz/EasyAdminBundle.git
$ git pull --rebase upstream master
$ git push -f origin the_name_of_your_branch
```

Further information
-------------------

 * [General GitHub documentation][gh-help]
 * [GitHub pull request documentation][gh-pr]

 [mit-license]: https://opensource.org/licenses/MIT
 [gh-help]: https://help.github.com
 [gh-pr]: https://help.github.com/send-pull-requests
 [easyadmin-demo]: https://github.com/javiereguiluz/easy-admin-demo
 [easyadmin-issues]: https://github.com/javiereguiluz/EasyAdminBundle/issues?utf8=%E2%9C%93&q=is%3Aissue
 [create-issue]: https://github.com/javiereguiluz/EasyAdminBundle/issues/new
 [create-pr]: https://github.com/javiereguiluz/EasyAdminBundle/pull/new
 [easyadmin-repository]: https://github.com/javiereguiluz/EasyAdminBundle
 [psr2-standard]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
 [symfony-standard]: https://github.com/symfony/symfony-standard
