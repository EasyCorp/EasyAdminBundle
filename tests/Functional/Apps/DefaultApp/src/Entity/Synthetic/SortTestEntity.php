<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entity for testing different sort types including association sorting.
 */
#[ORM\Entity]
class SortTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $textField = null;

    #[ORM\Column]
    private ?int $integerField = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateTimeField = null;

    /**
     * ManyToOne relationship for testing sort by association property.
     */
    #[ORM\ManyToOne(targetEntity: SortTestRelatedEntity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SortTestRelatedEntity $manyToOneRelation = null;

    /**
     * OneToMany relationship for testing sort by collection count.
     *
     * @var Collection<int, SortTestRelatedEntity>
     */
    #[ORM\OneToMany(targetEntity: SortTestRelatedEntity::class, mappedBy: 'sortTestEntity', cascade: ['persist'])]
    private Collection $oneToManyRelations;

    /**
     * ManyToMany relationship for testing sort by collection count.
     *
     * @var Collection<int, SortTestRelatedEntity>
     */
    #[ORM\ManyToMany(targetEntity: SortTestRelatedEntity::class)]
    #[ORM\JoinTable(name: 'sort_test_entity_many_to_many')]
    private Collection $manyToManyRelations;

    public function __construct()
    {
        $this->oneToManyRelations = new ArrayCollection();
        $this->manyToManyRelations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTextField(): ?string
    {
        return $this->textField;
    }

    public function setTextField(?string $textField): self
    {
        $this->textField = $textField;

        return $this;
    }

    public function getIntegerField(): ?int
    {
        return $this->integerField;
    }

    public function setIntegerField(?int $integerField): self
    {
        $this->integerField = $integerField;

        return $this;
    }

    public function getDateTimeField(): ?\DateTimeInterface
    {
        return $this->dateTimeField;
    }

    public function setDateTimeField(?\DateTimeInterface $dateTimeField): self
    {
        $this->dateTimeField = $dateTimeField;

        return $this;
    }

    public function getManyToOneRelation(): ?SortTestRelatedEntity
    {
        return $this->manyToOneRelation;
    }

    public function setManyToOneRelation(?SortTestRelatedEntity $manyToOneRelation): self
    {
        $this->manyToOneRelation = $manyToOneRelation;

        return $this;
    }

    /**
     * @return Collection<int, SortTestRelatedEntity>
     */
    public function getOneToManyRelations(): Collection
    {
        return $this->oneToManyRelations;
    }

    public function addOneToManyRelation(SortTestRelatedEntity $relation): self
    {
        if (!$this->oneToManyRelations->contains($relation)) {
            $this->oneToManyRelations->add($relation);
            $relation->setSortTestEntity($this);
        }

        return $this;
    }

    public function removeOneToManyRelation(SortTestRelatedEntity $relation): self
    {
        if ($this->oneToManyRelations->removeElement($relation)) {
            if ($relation->getSortTestEntity() === $this) {
                $relation->setSortTestEntity(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SortTestRelatedEntity>
     */
    public function getManyToManyRelations(): Collection
    {
        return $this->manyToManyRelations;
    }

    public function addManyToManyRelation(SortTestRelatedEntity $relation): self
    {
        if (!$this->manyToManyRelations->contains($relation)) {
            $this->manyToManyRelations->add($relation);
        }

        return $this;
    }

    public function removeManyToManyRelation(SortTestRelatedEntity $relation): self
    {
        $this->manyToManyRelations->removeElement($relation);

        return $this;
    }

    public function __toString(): string
    {
        return $this->textField ?? '';
    }
}
