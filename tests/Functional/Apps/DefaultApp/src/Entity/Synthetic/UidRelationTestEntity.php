<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UidRelationTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne]
    private ?UuidTestEntity $uuidRelation = null;

    #[ORM\ManyToOne]
    private ?UlidTestEntity $ulidRelation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUuidRelation(): ?UuidTestEntity
    {
        return $this->uuidRelation;
    }

    public function setUuidRelation(?UuidTestEntity $uuidRelation): self
    {
        $this->uuidRelation = $uuidRelation;

        return $this;
    }

    public function getUlidRelation(): ?UlidTestEntity
    {
        return $this->ulidRelation;
    }

    public function setUlidRelation(?UlidTestEntity $ulidRelation): self
    {
        $this->ulidRelation = $ulidRelation;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
