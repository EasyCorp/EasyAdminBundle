<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Address;

/**
 * One property per Doctrine type, to test the default fields that EasyAdmin
 * creates for each type. It only defines the mapping because tests only read its
 * metadata and never persist it.
 */
#[ORM\Entity]
class DoctrineTypesTestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $stringColumn = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $simpleArrayColumn = [];

    #[ORM\Column(type: Types::JSON)]
    private array $jsonColumn = [];

    #[ORM\Column]
    private bool $booleanColumn = false;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $textColumn = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateMutableColumn = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateImmutableColumn = null;

    #[ORM\Column]
    private ?\DateTime $dateTimeMutableColumn = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateTimeImmutableColumn = null;

    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTime $dateTimeTzMutableColumn = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $dateTimeTzImmutableColumn = null;

    #[ORM\Column]
    private ?int $integerColumn = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $smallintColumn = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $decimalColumn = null;

    #[ORM\Column]
    private ?float $floatColumn = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $timeMutableColumn = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $timeImmutableColumn = null;

    #[ORM\Embedded(class: Address::class)]
    private Address $address;
}
