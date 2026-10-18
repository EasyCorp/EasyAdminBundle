<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\ORM\Mapping as ORM;

/**
 * EasyAdmin doesn't support composite primary keys, so this entity is only used to
 * check that they are rejected. Tests only read its metadata and never persist it.
 */
#[ORM\Entity]
class CompositeKeyTestEntity
{
    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Id]
    #[ORM\Column]
    private ?int $year = null;
}
