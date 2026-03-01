<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

/**
 * Related entity for SortTestEntity relationship testing.
 */
#[ORM\Entity]
class SortTestRelatedEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Back-reference for OneToMany relationship in SortTestEntity.
     */
    #[ORM\ManyToOne(targetEntity: SortTestEntity::class, inversedBy: 'oneToManyRelations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?SortTestEntity $sortTestEntity = null;

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

    public function getSortTestEntity(): ?SortTestEntity
    {
        return $this->sortTestEntity;
    }

    public function setSortTestEntity(?SortTestEntity $sortTestEntity): self
    {
        $this->sortTestEntity = $sortTestEntity;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
