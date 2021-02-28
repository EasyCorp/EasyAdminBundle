<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class BatchActionDto
{
    private $name;
    private $entityIds;
    private $entityFqcn;
    private $referrerUrl;

    public function __construct(string $name, array $entityIds, string $entityFqcn, string $referrerUrl)
    {
        $this->name = $name;
        $this->entityIds = $entityIds;
        $this->entityFqcn = $entityFqcn;
        $this->referrerUrl = $referrerUrl;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEntityIds(): array
    {
        return $this->entityIds;
    }

    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    public function getReferrerUrl(): string
    {
        return $this->referrerUrl;
    }
}
