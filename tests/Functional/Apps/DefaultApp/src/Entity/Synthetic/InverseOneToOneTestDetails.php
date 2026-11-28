<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

/**
 * Owning side of the one-to-one association of InverseOneToOneTestEntity.
 */
#[ORM\Entity]
class InverseOneToOneTestDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: InverseOneToOneTestEntity::class, inversedBy: 'details')]
    private ?InverseOneToOneTestEntity $entity = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntity(): ?InverseOneToOneTestEntity
    {
        return $this->entity;
    }

    public function setEntity(InverseOneToOneTestEntity $entity): self
    {
        $this->entity = $entity;

        return $this;
    }
}
