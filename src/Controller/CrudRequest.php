<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Config\Entity;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;

final class CrudRequest
{
    private $adminContext;

    public function __construct(AdminContext $adminContext)
    {
        $this->adminContext = $adminContext;
    }

    public function getContext(): AdminContext
    {
        return $this->adminContext;
    }

    public function getReferrer(): ?string
    {
        return $this->adminContext->getRequest()->query->get('referrer');
    }

    public function getEntity(): Entity
    {
        $entityFqcn = $this->adminContext->getCrud()->getEntityFqcn();
        $entityId = $this->adminContext->getRequest()->query->get('entityId');
        $entityPermission = $this->adminContext->getCrud()->getEntityPermission();

        return new Entity($entityFqcn, $entityId, $entityPermission);
    }
}
