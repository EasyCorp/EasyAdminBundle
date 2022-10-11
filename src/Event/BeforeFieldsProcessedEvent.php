<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Event;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;

final class BeforeFieldsProcessedEvent
{
    private $entityDto;
    private $fieldCollection;
    private $pageName;

    public function __construct(EntityDto $entityDto, FieldCollection $fieldCollection, string $pageName)
    {
        $this->entityDto = $entityDto;
        $this->fieldCollection = $fieldCollection;
        $this->pageName = $pageName;
    }

    public function getEntityDto(): EntityDto
    {
        return $this->entityDto;
    }

    public function getFieldCollection(): FieldCollection
    {
        return $this->fieldCollection;
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }
}
