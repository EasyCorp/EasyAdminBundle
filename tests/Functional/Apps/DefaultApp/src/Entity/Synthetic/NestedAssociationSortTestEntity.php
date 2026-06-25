<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

/**
 * Root entity for nested association sort testing. Sortable by the nested association
 * property `category.parent` (using the leaf category's `name`).
 */
#[ORM\Entity]
class NestedAssociationSortTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: NestedAssociationSortTestCategory::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?NestedAssociationSortTestCategory $category = null;

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

    public function getCategory(): ?NestedAssociationSortTestCategory
    {
        return $this->category;
    }

    public function setCategory(?NestedAssociationSortTestCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
