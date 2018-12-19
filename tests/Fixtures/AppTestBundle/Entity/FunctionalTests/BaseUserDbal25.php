<?php

namespace AppTestBundle\Entity\FunctionalTests;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class BaseUserDbal25 extends BaseUser
{
    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="createdAtDateTimeImmutable", type="datetime")
     */
    private $createdAtDateTimeImmutable;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="createdAtDateImmutable", type="date")
     */
    private $createdAtDateImmutable;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="createdAtTimeImmutable", type="time")
     */
    private $createdAtTimeImmutable;

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAtDateTimeImmutable($createdAt)
    {
        $this->createdAtDateTimeImmutable = $createdAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAtDateTimeImmutable()
    {
        return $this->createdAtDateTimeImmutable;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAtDateImmutable($createdAt)
    {
        $this->createdAtDateImmutable = $createdAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAtDateImmutable()
    {
        return $this->createdAtDateImmutable;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAtTimeImmutable($createdAt)
    {
        $this->createdAtTimeImmutable = $createdAt;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAtTimeImmutable()
    {
        return $this->createdAtTimeImmutable;
    }
}
