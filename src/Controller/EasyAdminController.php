<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * This file is kept for BC reasons, to avoid errors when upgrading from EasyAdmin 2.
 */
class EasyAdminController extends AbstractController
{
    public function __call($name, $arguments)
    {
        throw new \RuntimeException(<<<HELP
If you are seeing this error, you are probably upgrading your application
from EasyAdmin 2 to EasyAdmin 3. The new version of this bundle is a complete
refactoring and most of the previous classes have been moved or deleted.

One of those removed classes is "EasyAdminController", which was used in EasyAdmin 2
as the base controller of the entire backend. In EasyAdmin 3 each Doctrine entity has
its own controller, so you can no longer use that base controller.

If your application config or code references to this removed controller, you'll see
this error message. Check out the following files:

1) Remove the "config/routes/easy_admin.yaml" file. This is no longer needed in EasyAdmin 3.

2) Check the contents of "config/routes.yaml" and remove any route related to that controller.

3) Check that your own admin controllers don't extend from the "EasyAdminController" class.

If you need more help, read the EasyAdmin 3 documentation at:
https://symfony.com/doc/master/bundles/EasyAdminBundle/index.html
HELP
        );
    }
}
