<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity\Synthetic\AdminRouteTestEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Test CRUD controller with multiple AdminRoute attributes on the same action.
 */
class MultipleRouteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdminRouteTestEntity::class;
    }

    #[AdminRoute('/action1', 'action1')]
    #[AdminRoute('/action1-alt', 'action1_alt')]
    public function customAction1(): Response
    {
        return new Response('Custom Action 1');
    }

    #[AdminRoute('/action2/path1', 'action2_path1', options: ['methods' => ['GET']])]
    #[AdminRoute('/action2/path2', 'action2_path2', options: ['methods' => ['GET']])]
    #[AdminRoute('/action2/path3', 'action2_path3', options: ['methods' => ['GET', 'POST']])]
    public function customAction2(): Response
    {
        return new Response('Custom Action 2');
    }

    #[AdminRoute('/action3/{entityId}', 'action3')]
    #[AdminRoute('/action3-alt/{entityId}', 'action3_alt')]
    public function customAction3($entityId): Response
    {
        return new Response('Custom Action 3: '.$entityId);
    }

    #[AdminRoute('/action4/{entityId}', 'action4')]
    #[AdminRoute(path: '/action4-alt/{entityId}')]
    public function customAction4($entityId): Response
    {
        return new Response('Custom Action 4: '.$entityId);
    }

    #[AdminRoute('/action5', 'action5')]
    #[AdminRoute(name: 'action5_alt')]
    public function customAction5(): Response
    {
        return new Response('Custom Action 5');
    }

    #[AdminRoute('/action6', 'action6')]
    #[AdminRoute]
    public function customAction6(): Response
    {
        return new Response('Custom Action 6');
    }

    // custom action using the "{id}" placeholder (alias of "{entityId}"). This is the cleaner,
    // forward-looking form that also lets Symfony's MapEntity resolve a typed argument natively.
    #[AdminRoute('/safe-delete-id/{id}', 'safe_delete_id', options: ['methods' => ['POST']])]
    public function safeDeleteById($id): Response
    {
        return new Response('Safe delete by id: '.$id);
    }

    // custom action that reads the entity loaded by EasyAdmin from the "{id}" placeholder, to prove
    // that AdminContext::getEntity() works the same with "{id}" as it does with "{entityId}".
    #[AdminRoute('/detail-by-id/{id}', 'detail_by_id', options: ['methods' => ['GET']])]
    public function detailById(AdminContext $context): Response
    {
        $instance = $context->getEntity()?->getInstance();

        return new Response(sprintf(
            'Detail by id: %s',
            $instance instanceof AdminRouteTestEntity ? $instance->getId().':'.$instance->getName() : 'NO ENTITY'
        ));
    }
}
