<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Actions;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends AbstractCrudController<Category>
 */
class BatchActionsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            BooleanField::new('active'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $batchActivate = Action::new('batchActivate', 'Activate')
            ->linkToCrudAction('batchActivate')
            ->addCssClass('btn btn-success')
            ->setIcon('fa fa-check');

        $batchDeactivate = Action::new('batchDeactivate', 'Deactivate')
            ->linkToCrudAction('batchDeactivate')
            ->addCssClass('btn btn-warning')
            ->setIcon('fa fa-times');

        return $actions
            ->addBatchAction($batchActivate)
            ->addBatchAction($batchDeactivate);
    }

    #[AdminRoute('/batch-activate', 'batch_activate', options: ['methods' => ['POST']])]
    public function batchActivate(BatchActionDto $batchActionDto): Response
    {
        if (!$this->isValidBatchActionCsrfToken($batchActionDto)) {
            return $this->redirectToIndex();
        }

        $entityManager = $this->container->get('doctrine')->getManagerForClass($batchActionDto->getEntityFqcn());
        $repository = $entityManager->getRepository($batchActionDto->getEntityFqcn());

        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entity = $repository->find($entityId);
            if (null !== $entity) {
                $entity->setActive(true);
            }
        }

        $entityManager->flush();

        $this->addFlash('success', sprintf('%d item(s) activated successfully.', \count($batchActionDto->getEntityIds())));

        return $this->redirectToIndex();
    }

    #[AdminRoute('/batch-deactivate', 'batch_deactivate', options: ['methods' => ['POST']])]
    public function batchDeactivate(BatchActionDto $batchActionDto): Response
    {
        if (!$this->isValidBatchActionCsrfToken($batchActionDto)) {
            return $this->redirectToIndex();
        }

        $entityManager = $this->container->get('doctrine')->getManagerForClass($batchActionDto->getEntityFqcn());
        $repository = $entityManager->getRepository($batchActionDto->getEntityFqcn());

        foreach ($batchActionDto->getEntityIds() as $entityId) {
            $entity = $repository->find($entityId);
            if (null !== $entity) {
                $entity->setActive(false);
            }
        }

        $entityManager->flush();

        $this->addFlash('success', sprintf('%d item(s) deactivated successfully.', \count($batchActionDto->getEntityIds())));

        return $this->redirectToIndex();
    }

    private function isValidBatchActionCsrfToken(BatchActionDto $batchActionDto): bool
    {
        return $this->isCsrfTokenValid('ea-batch-action-'.$batchActionDto->getName().'-'.$batchActionDto->getEntityFqcn(), $batchActionDto->getCsrfToken());
    }

    private function redirectToIndex(): Response
    {
        return $this->redirect($this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl());
    }
}
