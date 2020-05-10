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
refactorization and most of the previous classes have been moved or deleted.
 
One of those removed classes is "EasyAdminController", which was used in EasyAdmin 2
as the base controller of the entire backend. In EasyAdmin 3 each Doctrine entity has
its own controller, so you can no longer use that base controller.

This file is usually referenced from the "config/routes/easy_admin.yaml" file. Remove
or update that configuration file to stop using the removed controller class.

If you need more help, read the EasyAdmin 3 documentation at:
https://symfony.com/doc/master/bundles/EasyAdminBundle/index.html
HELP
        );
    }
}
