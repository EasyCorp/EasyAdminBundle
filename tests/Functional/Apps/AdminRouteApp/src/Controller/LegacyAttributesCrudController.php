<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test CRUD controller that uses the legacy #[AdminCrud] and #[AdminAction] attributes.
 * These attributes are deprecated in favor of #[AdminRoute], but must continue working
 * for backward compatibility.
 *
 * IDE marks this file as unused, but it's used in AdminRouteTest. EasyAdmin's route loader
 * discovers the routes defined here and that test ensures they work as expected.
 *
 * @deprecated The #[AdminCrud] and #[AdminAction] attributes are deprecated. Use #[AdminRoute] instead.
 */
#[AdminCrud(routePath: '/legacy-crud', routeName: 'legacy_crud')]
class LegacyAttributesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    // test #[AdminAction] with custom path and name
    #[AdminAction(routePath: '/custom-index', routeName: 'custom_index')]
    public function index(AdminContext $context)
    {
        return parent::index($context);
    }

    // test #[AdminAction] with only custom path
    #[AdminAction(routePath: '/custom-detail/{entityId}')]
    public function detail(AdminContext $context)
    {
        return parent::detail($context);
    }

    // test #[AdminAction] with only custom name
    #[AdminAction(routeName: 'create_new')]
    public function new(AdminContext $context)
    {
        return parent::new($context);
    }

    // test #[AdminAction] with positional arguments (legacy syntax)
    #[AdminAction('/custom-edit/{entityId}', 'modify')]
    public function edit(AdminContext $context)
    {
        return parent::edit($context);
    }

    // test custom action with #[AdminAction]
    #[AdminAction(routePath: '/export', routeName: 'export_data')]
    public function exportAction(): Response
    {
        return new Response('Legacy export action');
    }

    // keep delete without customization to test default behavior
}
