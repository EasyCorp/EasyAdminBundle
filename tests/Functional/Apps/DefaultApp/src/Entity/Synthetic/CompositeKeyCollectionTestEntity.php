<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An entity with a simple primary key and a to-many association to an entity with
 * a composite primary key (e.g. a join entity with extra columns). Tests only read
 * its metadata and never persist it.
 */
#[ORM\Entity]
class CompositeKeyCollectionTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, CompositeKeyTestEntity>
     */
    #[ORM\OneToMany(targetEntity: CompositeKeyTestEntity::class, mappedBy: 'parent')]
    private Collection $compositeKeyEntities;

    public function __construct()
    {
        $this->compositeKeyEntities = new ArrayCollection();
    }
}
