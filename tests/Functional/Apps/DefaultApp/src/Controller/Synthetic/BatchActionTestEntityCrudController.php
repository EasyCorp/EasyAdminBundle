<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\BatchActionTestEntity;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<BatchActionTestEntity>
 */
class BatchActionTestEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BatchActionTestEntity::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            BooleanField::new('active'),
            TextField::new('status'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        // add custom batch action to activate items
        $batchActivate = Action::new('batchActivate', 'Activate')
            ->linkToCrudAction('batchActivate')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check');

        // add custom batch action to deactivate items
        $batchDeactivate = Action::new('batchDeactivate', 'Deactivate')
            ->linkToCrudAction('batchDeactivate')
            ->addCssClass('btn btn-warning')
            ->setIcon('fa fa-times');

        // note: BATCH_DELETE is already added by default in AbstractDashboardController
        // we only need to add our custom batch actions
        return $actions
            ->addBatchAction($batchActivate)
            ->addBatchAction($batchDeactivate);
    }

    #[AdminRoute('/batch-activate', 'batch_activate', options: ['methods' => ['POST']])]
    public function batchActivate(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $entityManager = $this->container->get('doctrine')->getManagerForClass($batchActionDto->getEntityFqcn());
        $repository = $entityManager->getRepository($batchActionDto->getEntityFqcn());

        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entity = $repository->find($entityId);
            if (null !== $entity) {
                $entity->setActive(true);
                $entity->setStatus('activated');
            }
        }

        $entityManager->flush();

        $this->addFlash('success', sprintf('%d item(s) activated successfully.', \count($batchActionDto->getEntityIds())));

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }

    #[AdminRoute('/batch-deactivate', 'batch_deactivate', options: ['methods' => ['POST']])]
    public function batchDeactivate(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $entityManager = $this->container->get('doctrine')->getManagerForClass($batchActionDto->getEntityFqcn());
        $repository = $entityManager->getRepository($batchActionDto->getEntityFqcn());

        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entity = $repository->find($entityId);
            if (null !== $entity) {
                $entity->setActive(false);
                $entity->setStatus('deactivated');
            }
        }

        $entityManager->flush();

        $this->addFlash('success', sprintf('%d item(s) deactivated successfully.', \count($batchActionDto->getEntityIds())));

        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }
}
