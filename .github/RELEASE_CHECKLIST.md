How to release a new EasyAdmin version
======================================

1. Update the value of the `EasyAdminBundle::VERSION` constant (in the
   `EasyAdminBundle.php` file) to remove the `-DEV` suffix and prepare the
   stable release. Example: change `1.12.6-DEV` to `1.12.6`
2. Update the version number in the `README.md` link that points to the stable
   version documentation. Example: change `1.12.5` to `1.12.6`
3. Compile the CSS and JavaScript assets using the `compile_assets.sh` script
   provided in `Resources/bin/`
4. Create the tag and sign it. Example: `git tag -s v1.12.6` (the tag version is
   always prefixed with `v`, but remove it for the tag comment: `1.12.6`).
5. Push the tag to GitHub. Example: `git push origin v1.12.6`
6. Prepare the changelog of the new version with the custom `changelog` Git
   command. Example: `git changelog v1.12.5` (the version passed to the command
   is the previous version used as a reference to list the changes).
7. Go to https://github.com/javiereguiluz/EasyAdminBundle/releases and click
   on `Draft a new release`. Select the tag pushed before and paste the
   changelog contents.
8. Update again the value of the `EasyAdminBundle::VERSION` constant to start
   the development of the next version. Example: change `1.12.6` to `1.12.7-DEV`

Resources
---------

The custom `changelog` Git command used to generate the version changelog can
be defined as a global Git alias:

    $ git config --global alias.changelog "!f() { git log $1...$2 --pretty=format:'[%h] %s' --reverse; } ; f"
