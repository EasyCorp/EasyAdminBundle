<?php

namespace AppTestBundle\Entity\FunctionalTests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $email;

    /**
     * @var Purchase[]
     *
     * @ORM\OneToMany(targetEntity="Purchase", mappedBy="buyer", cascade={"remove"})
     */
    private $purchases;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="createdAtDateTimeImmutable", type="datetime_immutable")
     */
    private $createdAtDateTimeImmutable;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="createdAtDateImmutable", type="date_immutable")
     */
    private $createdAtDateImmutable;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="createdAtTimeImmutable", type="time_immutable")
     */
    private $createdAtTimeImmutable;

    public function __toString()
    {
        return $this->username;
    }

    public function __construct()
    {
        $this->purchases = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param Purchase[] $purchases
     */
    public function setPurchases($purchases)
    {
        $this->purchases = $purchases;
    }

    /**
     * @return Purchase[]
     */
    public function getPurchases()
    {
        return $this->purchases;
    }

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
